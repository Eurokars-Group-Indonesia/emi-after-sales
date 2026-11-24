<?php

namespace App\Services\SyncMsi;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * Sumber data Sync MSI di database WRS Connect (MySQL) — port query upload_customer_csi.php.
 *
 * Pemetaan tabel lama (PostgreSQL WRS After Sales) → WRS Connect:
 *   master_aftersales.tblcustomer                  → tblcustomer
 *   master_aftersales.tblcustomer_log              → tblcustomer_log
 *   transaksi_aftersales.tblkpi                    → tblkpi
 *   transaksi_aftersales.tblwork_planning          → tblwork_planning
 *   public.tblreligion (kd_religion, nm_religion)  → ms_religion (religion_id, religion)
 *   public.tblinventory                            → tblinventory
 *   master_aftersales.tbldealer_personil_aftersales→ afsel_dealer_personel (dealer_personel_id, name, dealer_level_personel_id)
 *   transaksi_sales.tblfaktur                      → tblfaktur
 *
 * Kolom hasil query dibungkus satu derived table "src" sehingga tblsetting.filter_csi bisa memakai nama kolom
 * yang sama seperti di script lama (type_customer, service_type, fk_model, tanggal_handover, dst).
 */
class CsiCustomerSource
{
    /**
     * @param  Carbon       $from         log_action_date >= from 00:00:00
     * @param  Carbon       $to           log_action_date <= to 23:59:59
     * @param  Carbon       $wpFrom       tgl_work_planning >= (script lama: last_update_csi - 1 bulan)
     * @param  string|null  $filterCsi    tblsetting.filter_csi (potongan WHERE, dipercaya karena hanya admin yang mengubah)
     * @return LazyCollection<int, object>
     */
    public function rows(Carbon $from, Carbon $to, Carbon $wpFrom, ?string $filterCsi): LazyCollection
    {
        [$sql, $bindings] = $this->query($from, $to, $wpFrom, $filterCsi);

        return LazyCollection::make(fn () => DB::connection()->cursor($sql, $bindings));
    }

    /** Jumlah baris (dipakai untuk info / dry-run). */
    public function count(Carbon $from, Carbon $to, Carbon $wpFrom, ?string $filterCsi): int
    {
        [$sql, $bindings] = $this->query($from, $to, $wpFrom, $filterCsi);

        return (int) DB::selectOne("select count(*) as n from ({$sql}) as cnt", $bindings)->n;
    }

    /** @return array{0: string, 1: array} */
    public function query(Carbon $from, Carbon $to, Carbon $wpFrom, ?string $filterCsi): array
    {
        $logFrom = $from->copy()->startOfDay()->format('Y-m-d H:i:s');
        $logTo   = $to->copy()->setTime(23, 59, 59)->format('Y-m-d H:i:s');
        $wpStart = $wpFrom->copy()->startOfDay()->format('Y-m-d H:i:s');

        // Customer yang punya log di rentang (dipakai juga untuk membatasi tblkpi / tblwork_planning supaya ringan)
        $logCustomers = 'select kd_customer from tblcustomer_log where log_action_date >= ? and log_action_date <= ?';

        $sql = "select src.* from (
            select
                c.kd_customer, c.alamat as alamat_wrs, c.fk_dealer as dealer_wrs, c.nama_customer, c.nm_pemakai,
                c.tanggal_lahir, c.fk_religion, c.telephone_1, c.telephone_2, c.email, c.alamat, c.nomor_polisi,
                c.no_vin, c.tanggal_penyerahan, c.type_customer, c.last_odo_meter, c.tanggal_last_service,
                l.log_action_date, l.log_action_userid,
                k.customer_request as cr, k.fk_customer, k.tanggal_faktur, k.customer_request, k.service_type,
                k.fk_category_1_vehicles, k.fk_category_2_vehicles,
                wp.fk_service_advisor,
                r.religion as nm_religion,
                inv.vin, inv.model, inv.fk_model, inv.tahun_pembuatan,
                sa.name,
                f.tanggal_handover, f.f_vin
            from tblcustomer c
            inner join (
                select distinct kd_customer, log_action_date, log_action_userid, tanggal_last_service
                from tblcustomer_log
                where log_action_date >= ? and log_action_date <= ?
            ) l on c.kd_customer = l.kd_customer
            left join (
                select distinct fk_customer, tanggal_faktur, customer_request, service_type, fk_category_1_vehicles, fk_category_2_vehicles
                from tblkpi
                where fk_customer in ({$logCustomers})
            ) k on k.fk_customer = c.kd_customer and k.tanggal_faktur = c.tanggal_last_service
            left join (
                select distinct fk_customer, fk_service_advisor
                from tblwork_planning
                where tgl_work_planning >= ? and tgl_work_planning <= ?
                  and fk_customer in ({$logCustomers})
            ) wp on wp.fk_customer = k.fk_customer
            left join ms_religion r on r.religion_id = c.fk_religion
            inner join (
                select no_vin as vin, fk_model as model, fk_model, tahun_pembuatan
                from tblinventory
                where use_by = 'Faktur'
            ) inv on inv.vin = c.no_vin
            left join (
                select dealer_personel_id, name
                from afsel_dealer_personel
                where dealer_level_personel_id = ? and is_active = 1
            ) sa on sa.dealer_personel_id = wp.fk_service_advisor
            left join (
                select tanggal_handover, fk_vin as f_vin from tblfaktur
            ) f on f.f_vin = c.no_vin
        ) src";

        $bindings = [
            $logFrom, $logTo,                       // l
            $logFrom, $logTo,                       // k (logCustomers)
            $wpStart, $logTo, $logFrom, $logTo,     // wp + logCustomers
            (string) config('sync_msi.sa_level_id'),
        ];

        $filter = $this->normalizeFilter($filterCsi);
        if ($filter !== '') {
            $sql .= "\nwhere ({$filter})";
        }

        $sql .= "\norder by src.dealer_wrs, src.log_action_date";

        return [$sql, $bindings];
    }

    /** Script lama: str_replace("\\", "", $filter_csi). Juga buang "where" di depan jika ada. */
    public function normalizeFilter(?string $filterCsi): string
    {
        $filter = trim(str_replace('\\', '', (string) $filterCsi));

        return trim((string) preg_replace('/^where\s+/i', '', $filter));
    }

    /**
     * Ubah satu baris sumber menjadi nilai untuk dbo.members, mengikuti script lama.
     * Semua nilai string (null → '') karena script lama menyusun SQL dari string.
     */
    public function transform(object $row, Carbon $now): array
    {
        [$firstStnk, $lastStnk] = $this->splitName($row->nama_customer ?? null);
        [$first, $last]         = $this->splitName($row->nm_pemakai ?? null);

        $birth = $this->date($row->tanggal_lahir ?? null);
        $hand  = $this->date($row->tanggal_last_service ?? null);

        [$kdService, $days] = $this->service($row->fk_category_1_vehicles ?? null, $row->fk_category_2_vehicles ?? null);

        // Script lama: date("n/d/Y h:i:s", strtotime($tgl_penyerahan.'+N days')). Jam 00 dengan format "h" (12 jam)
        // menghasilkan "12:00:00" yang dibaca SQL Server sebagai jam 12 siang — dipertahankan agar data konsisten.
        $nextLegacy = '';
        $next       = null;
        if ($kdService !== '' && $days !== null) {
            $base       = $hand ? $hand->copy()->startOfDay() : $now->copy();
            $nextLegacy = $base->copy()->addDays($days)->format('n/d/Y h:i:s');
            $next       = Carbon::createFromFormat('!n/d/Y H:i:s', $nextLegacy, $now->getTimezone());
        }

        return [
            'kd_customer'      => (string) ($row->kd_customer ?? ''),
            'log_id'           => (string) ($row->log_action_userid ?? ''),
            'log_date'         => $row->log_action_date ?? null,
            'fk_dealer'        => (string) ($row->dealer_wrs ?? ''),
            'fk_model'         => (string) ($row->model ?? ''),
            'first_name_stnk'  => $this->clean($firstStnk),
            'last_name_stnk'   => $this->clean($lastStnk),
            'first_name'       => $this->clean($first),
            'last_name'        => $this->clean($last),
            'birth'            => $birth ?? Carbon::create(1901, 1, 1, 0, 0, 0, $now->getTimezone()),
            'birth_legacy'     => $birth ? $birth->format('Y-n-d') : '1901-01-01',
            'religion'         => $this->clean($row->nm_religion ?? null),
            'mobile_phone'     => $this->clean($row->telephone_2 ?? null),
            'home_phone'       => $this->clean($row->telephone_1 ?? null),
            'email'            => $this->clean($row->email ?? null),
            'address'          => $this->clean($row->alamat_wrs ?? null),
            'plate_number'     => $this->clean($row->nomor_polisi ?? null),
            'vin'              => $this->clean($row->no_vin ?? null),
            'handover'         => $hand,
            'handover_legacy'  => $hand ? $hand->format('Y-n-d') : '',
            'next_service'     => $next,
            'next_legacy'      => $nextLegacy,
            'dealer_staff'     => $this->clean($row->name ?? null),
            'reason'           => $kdService,
            'other_reason'     => $this->clean($row->cr ?? null),
            'who_brings'       => $this->clean($row->type_customer ?? null),
            'car_km'           => $this->clean($this->number($row->last_odo_meter ?? null)),
            'expire'           => $now->copy()->startOfDay()->addDays(5),
            'expire_legacy'    => $now->copy()->addDays(5)->format('Y-n-d'),
        ];
    }

    /** @return array{0: string, 1: ?int} [reason_for_service, hari ke next service] */
    public function service(?string $cat1, ?string $cat2): array
    {
        $cat1 = (string) $cat1;
        $cat2 = (string) $cat2;

        foreach (config('sync_msi.service_map', []) as $m) {
            if ($cat1 === (string) $m['cat1'] && $cat2 === (string) $m['cat2']) {
                return [(string) $m['code'], $m['days']];
            }
        }

        return ['', null];
    }

    /**
     * Script lama: nama dipisah di spasi pertama. Tidak ada spasi (atau spasi di awal) → semua jadi nama depan.
     *
     * @return array{0: string, 1: string}
     */
    public function splitName(?string $name): array
    {
        $name = (string) $name;
        $pos  = strpos($name, ' ');

        if ($pos === false || $pos === 0) {
            return [$name, ''];
        }

        return [substr($name, 0, $pos), substr($name, $pos + 1)];
    }

    /** Script lama mengganti tanda petik tunggal dengan spasi sebelum dikirim ke MSI. */
    private function clean(?string $value): string
    {
        return str_replace("'", ' ', (string) $value);
    }

    /** decimal MySQL "12345.00" → "12345" (PostgreSQL numeric lama tidak punya .00, dan car_km di MSI bisa bertipe angka). */
    private function number($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (string) $value;
        if (preg_match('/^-?\d+\.\d+$/', $value)) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return $value;
    }

    private function date($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}

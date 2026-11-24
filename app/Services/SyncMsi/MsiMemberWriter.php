<?php

namespace App\Services\SyncMsi;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Penulisan ke database MSI (SQL Server) — port bagian COM ADODB di upload_customer_csi.php.
 *
 *  - Hanya SELECT, INSERT dan UPDATE ke dbo.members. TIDAK ADA DELETE.
 *  - Semua nilai dikirim sebagai parameter (bukan disambung ke SQL seperti script lama).
 *  - Lookup dbo.dealers / dbo.users / dbo.mazda_model di-cache per eksekusi.
 *  - Format tanggal mengikuti tipe kolom di dbo.members: kolom date/datetime → format aman 'YYYYMMDD HH:MM:SS'
 *    (tidak bergantung setting bahasa SQL Server); kolom teks → format string persis seperti script lama.
 */
class MsiMemberWriter
{
    private const DATE_TYPES = ['date', 'datetime', 'datetime2', 'smalldatetime', 'datetimeoffset'];

    private ConnectionInterface $db;

    /** @var array<string, string> kolom dbo.members → tipe data (lowercase) */
    private array $columnTypes = [];

    private array $dealers = [];
    private array $users = [];
    private array $models = [];

    /** Kunci member yang "di-insert" selama dry-run, supaya baris berikutnya terhitung UPDATE seperti eksekusi asli. */
    private array $dryInserted = [];

    public function __construct(string $connection, private bool $dryRun = false)
    {
        $this->db = DB::connection($connection);

        if ($this->db->getDriverName() !== 'sqlsrv') {
            throw new RuntimeException("Koneksi MSI harus memakai driver SQL Server (sqlsrv), bukan '{$this->db->getDriverName()}'.");
        }

        foreach ($this->db->select(
            "select COLUMN_NAME as name, DATA_TYPE as type from INFORMATION_SCHEMA.COLUMNS where TABLE_SCHEMA = 'dbo' and TABLE_NAME = 'members'"
        ) as $col) {
            $this->columnTypes[strtolower($col->name)] = strtolower($col->type);
        }

        if (!$this->columnTypes) {
            throw new RuntimeException('Tabel dbo.members tidak ditemukan di database MSI (cek nama database di menu Koneksi Database Sync).');
        }
    }

    /**
     * Cek dealer & user terdaftar tepat satu kali di MSI (sama dengan script lama: count(id) == 1).
     *
     * @return array{0: ?int, 1: ?int, 2: ?string} [id dealer, id user, alasan dilewati]
     */
    public function resolve(string $dealerCode, string $userId): array
    {
        $dealer = $this->dealers[$dealerCode] ??= $this->db->select('select id from dbo.dealers where dealer_code = ?', [$dealerCode]);
        $user   = $this->users[$userId] ??= $this->db->select('select id from dbo.users where kd_user_wrs = ?', [$userId]);

        $reasons = [];
        if (count($dealer) !== 1) {
            $reasons[] = count($dealer) === 0
                ? "Dealer '{$dealerCode}' belum terdaftar di MSI (dbo.dealers)."
                : "Dealer '{$dealerCode}' terdaftar " . count($dealer) . 'x di MSI (dbo.dealers).';
        }
        if (count($user) !== 1) {
            $reasons[] = count($user) === 0
                ? "User '{$userId}' belum terdaftar di MSI (dbo.users.kd_user_wrs)."
                : "User '{$userId}' terdaftar " . count($user) . 'x di MSI (dbo.users.kd_user_wrs).';
        }

        if ($reasons) {
            return [null, null, implode(' ', $reasons)];
        }

        return [(int) $dealer[0]->id, (int) $user[0]->id, null];
    }

    /**
     * INSERT member baru atau UPDATE member yang sudah ada, dalam satu transaksi (rollback jika gagal).
     *
     * @return array{action: string, affected: int}
     */
    public function upsert(array $r, int $dealerId, int $userId, Carbon $now): array
    {
        $modelId  = $this->modelId($r['fk_model']);
        $handover = $this->dateValue('handover_date', $r['handover'], $r['handover_legacy']);
        $exists   = $this->exists($r['vin'], $r['fk_dealer'], $handover);

        if ($this->dryRun) {
            $key = $this->key($r['vin'], $r['fk_dealer'], $handover);
            $action = ($exists || isset($this->dryInserted[$key])) ? 'UPDATE' : 'INSERT';
            $this->dryInserted[$key] = true;

            return ['action' => $action, 'affected' => 0];
        }

        $common = [
            'first_name_stnk' => $r['first_name_stnk'],
            'last_name_stnk'  => $r['last_name_stnk'],
            'first_name'      => $r['first_name'],
            'last_name'       => $r['last_name'],
            'date_of_birth'   => $this->dateValue('date_of_birth', $r['birth'], $r['birth_legacy']),
            'religion'        => $r['religion'],
            'mobile_phone'    => $r['mobile_phone'],
            'home_phone'      => $r['home_phone'],
            'email'           => $r['email'],
            'address'         => $r['address'],
            'mazda_car'       => $modelId,
            'plate_number'    => $r['plate_number'],
        ];

        return $this->db->transaction(function () use ($exists, $r, $common, $handover, $modelId, $dealerId, $userId, $now) {
            if (!$exists) {
                $nowValue = $this->dateValue('create_date', $now, $now->format('Y-n-d H:i:s'));

                $this->db->insert(
                    'insert into dbo.members (type, first_name_stnk, last_name_stnk, first_name, last_name, date_of_birth, religion,
                        mobile_phone, pre_home_phone, home_phone, email, address, mazda_car, plate_number, vin, eligible, handover_date,
                        next_service_date, dealer_staff_code, reason_for_service, other_reason_detail, who_brings_the_car_for_service,
                        car_km, status, create_date, create_by, modify_date, modify_by, unique_code, unique_code_expire_date,
                        service_dealer_id, shipment_address, is_have_mazda_before, mazda_car_before, dealer_id_before, is_sms_sent,
                        is_survey_complete, is_sms_resent, follow_up_by_sa_about_purchase)
                     values (\'CSI\', ?, ?, ?, ?, ?, ?, ?, \'0000\', ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?,
                        dbo.fncUniqueCodeGenerator(10), ?, ?, ?, 0, 0, 0, 0, 0, 0, 0)',
                    [
                        $common['first_name_stnk'], $common['last_name_stnk'], $common['first_name'], $common['last_name'],
                        $common['date_of_birth'], $common['religion'], $common['mobile_phone'], $common['home_phone'],
                        $common['email'], $common['address'], $modelId, $common['plate_number'], $r['vin'], $handover,
                        $this->dateValue('next_service_date', $r['next_service'], $r['next_legacy']),
                        $r['dealer_staff'], $r['reason'], $r['other_reason'], $r['who_brings'], $r['car_km'],
                        $nowValue, $userId,
                        $this->dateValue('modify_date', $now, $now->format('Y-n-d H:i:s')), $userId,
                        $this->dateValue('unique_code_expire_date', $r['expire'], $r['expire_legacy']),
                        $dealerId, $r['address'],
                    ]
                );

                return ['action' => 'INSERT', 'affected' => 1];
            }

            // Sama dengan script lama: modify_date / modify_by tidak diubah saat update.
            $affected = $this->db->update(
                'update dbo.members set first_name_stnk = ?, last_name_stnk = ?, first_name = ?, last_name = ?, date_of_birth = ?,
                    religion = ?, mobile_phone = ?, pre_home_phone = \'0000\', home_phone = ?, email = ?, address = ?, mazda_car = ?,
                    plate_number = ?, next_service_date = ?, dealer_staff_code = ?, reason_for_service = ?, other_reason_detail = ?,
                    who_brings_the_car_for_service = ?, car_km = ?, status = 1, shipment_address = ?
                 where vin = ? and handover_date = ? and service_dealer_id = ? and type = \'CSI\'',
                [
                    $common['first_name_stnk'], $common['last_name_stnk'], $common['first_name'], $common['last_name'],
                    $common['date_of_birth'], $common['religion'], $common['mobile_phone'], $common['home_phone'],
                    $common['email'], $common['address'], $modelId, $common['plate_number'],
                    $this->dateValue('next_service_date', $r['next_service'], $r['next_legacy']),
                    $r['dealer_staff'], $r['reason'], $r['other_reason'], $r['who_brings'], $r['car_km'], $r['address'],
                    $r['vin'], $handover, $dealerId,
                ]
            );

            return ['action' => 'UPDATE', 'affected' => $affected];
        });
    }

    /** Script lama: select vin, handover_date, dealer_code from dbo.members inner join dbo.dealers ... */
    private function exists(string $vin, string $dealerCode, string $handover): bool
    {
        return (bool) $this->db->selectOne(
            'select top 1 1 as x from dbo.members m inner join dbo.dealers d on m.service_dealer_id = d.id
             where m.vin = ? and d.dealer_code = ? and m.handover_date = ?',
            [$vin, $dealerCode, $handover]
        );
    }

    /** dbo.mazda_model.id dari kd_model_wrs; tidak ditemukan → '' (script lama). */
    private function modelId(string $fkModel): string
    {
        if (!array_key_exists($fkModel, $this->models)) {
            $id = $this->db->selectOne('select top 1 id from dbo.mazda_model where kd_model_wrs = ?', [$fkModel])?->id;
            $this->models[$fkModel] = $id === null ? '' : (string) $id;
        }

        return $this->models[$fkModel];
    }

    /**
     * Nilai tanggal untuk kolom MSI. Kosong → '' (script lama mengirim '' sehingga SQL Server menyimpan 1900-01-01).
     */
    private function dateValue(string $column, ?Carbon $value, string $legacy): string
    {
        if ($value === null) {
            return '';
        }

        $type = $this->columnTypes[$column] ?? 'datetime';
        if (!in_array($type, self::DATE_TYPES, true)) {
            return $legacy;
        }

        return $value->format('Ymd H:i:s');
    }

    private function key(string $vin, string $dealer, string $handover): string
    {
        return $vin . '|' . $dealer . '|' . $handover;
    }
}

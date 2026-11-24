<?php

namespace App\Services\Afsel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

/**
 * tblcustomer_log: snapshot baris tblcustomer SESUDAH ditambah / diubah (mengikuti WRS After Sales lama).
 * Dipakai Sync MSI (Customer CSI) untuk mencari customer yang berubah di rentang tanggal (log_action_date).
 *
 * Panggil di DALAM transaksi yang sama dengan insert/update tblcustomer, setelah perubahan dijalankan,
 * supaya log ikut rollback bila perubahan customer gagal.
 *
 * Nomor pk_id_log dari Document Numbering ('tblcustomer_log'). Untuk banyak baris, reserve nomornya
 * SEBELUM transaksi utama (lihat DocumentNumberService) lalu kirim lewat $pkIds.
 */
class CustomerLogService
{
    public const MODE_INSERT = 'I';
    public const MODE_UPDATE = 'UA';

    public const TABLE_NAME = 'tblcustomer_log';

    /** Kolom tblcustomer yang di-snapshot. Kolom approval di log memakai akhiran _1. */
    private const COLUMNS = [
        'kd_customer'          => 'kd_customer',
        'nomor_polisi'         => 'nomor_polisi',
        'type_customer'        => 'type_customer',
        'nama_customer'        => 'nama_customer',
        'telephone_1'          => 'telephone_1',
        'telephone_2'          => 'telephone_2',
        'alamat'               => 'alamat',
        'email'                => 'email',
        'no_vin'               => 'no_vin',
        'no_mesin'             => 'no_mesin',
        'fk_model'             => 'fk_model',
        'fk_type'              => 'fk_type',
        'fk_color'             => 'fk_color',
        'tahun'                => 'tahun',
        'tanggal_penyerahan'   => 'tanggal_penyerahan',
        'tanggal_last_service' => 'tanggal_last_service',
        'fk_dealer'            => 'fk_dealer',
        'status_customer'      => 'status_customer',
        'nm_pemakai'           => 'nm_pemakai',
        'fk_user_approval'     => 'fk_user_approval_1',
        'tgl_approval'         => 'tgl_approval_1',
        'status_approval'      => 'status_approval_1',
        'note_approval'        => 'note_approval_1',
        'last_odo_meter'       => 'last_odo_meter',
        'fk_religion'          => 'fk_religion',
        'nm_dpn_pemakai'       => 'nm_dpn_pemakai',
        'nm_dpn_customer'      => 'nm_dpn_customer',
        'tanggal_lahir'        => 'tanggal_lahir',
        'counter_repurchase'   => 'counter_repurchase',
    ];

    public function __construct(private DocumentNumberService $numbers)
    {
    }

    /** Reserve nomor pk_id_log untuk $count baris (panggil sebelum transaksi utama). */
    public function reserve(int $count): array
    {
        return $this->numbers->reserve(self::TABLE_NAME, $count);
    }

    /**
     * Tulis snapshot tblcustomer untuk kd_customer yang diberikan.
     *
     * @param  string[]       $kdCustomers  kd_customer yang baru di-insert / di-update
     * @param  string         $mode         self::MODE_INSERT / self::MODE_UPDATE
     * @param  string[]|null  $pkIds        nomor pk_id_log yang sudah direserve (minimal sebanyak $kdCustomers);
     *                                      null = reserve di sini
     * @return int jumlah baris log yang ditulis
     */
    public function log(array $kdCustomers, string $mode, ?array $pkIds = null, ?Carbon $at = null): int
    {
        if (!in_array($mode, [self::MODE_INSERT, self::MODE_UPDATE], true)) {
            throw new InvalidArgumentException("log_action_mode tidak dikenal: {$mode}");
        }
        if (DB::transactionLevel() < 1) {
            throw new LogicException('tblcustomer_log harus ditulis di dalam transaksi yang sama dengan perubahan tblcustomer.');
        }

        $kdCustomers = array_values(array_unique(array_filter($kdCustomers, fn ($kd) => $kd !== null && $kd !== '')));
        if (!$kdCustomers) {
            return 0;
        }

        $pkIds ??= $this->reserve(count($kdCustomers));
        if (count($pkIds) < count($kdCustomers)) {
            throw new InvalidArgumentException('Nomor pk_id_log yang direserve kurang dari jumlah customer.');
        }

        $at       = $at ?? now();
        $userId   = (string) (session('user.id') ?: 'SYSTEM');
        $username = (string) (session('user.username') ?: $userId);

        $written = 0;
        $i = 0;
        foreach (array_chunk($kdCustomers, 500) as $chunk) {
            $customers = DB::table('tblcustomer')
                ->whereIn('kd_customer', $chunk)
                ->get(array_keys(self::COLUMNS))
                ->keyBy('kd_customer');

            $rows = [];
            foreach ($chunk as $kd) {
                $c = $customers->get($kd);
                if (!$c) {
                    continue;
                }

                $row = ['pk_id_log' => $pkIds[$i++]];
                foreach (self::COLUMNS as $from => $to) {
                    $row[$to] = $c->{$from};
                }
                $row['log_action_userid']   = mb_substr($userId, 0, 28);
                $row['log_action_username'] = mb_substr($username, 0, 255);
                $row['log_action_date']     = $at->format('Y-m-d H:i:s');
                $row['log_action_mode']     = $mode;

                $rows[] = $row;
            }

            foreach (array_chunk($rows, 200) as $insert) {
                DB::table(self::TABLE_NAME)->insert($insert);
            }
            $written += count($rows);
        }

        return $written;
    }
}

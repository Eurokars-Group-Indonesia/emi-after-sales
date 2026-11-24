<?php

namespace App\Console\Commands;

use App\Services\Afsel\AfselRbacService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sync user After Sales dari database WRS (Postgres, schema master_aftersales)
 * ke tabel lokal ms_afsel_atpm_user / ms_afsel_dealer_user.
 *
 * Contoh:
 *   php artisan afsel:sync-users atpm --default-role=user
 *   php artisan afsel:sync-users dealer --default-role=user
 *   php artisan afsel:sync-users atpm --overwrite-password
 */
class AfselSyncUsers extends Command
{
    protected $signature = 'afsel:sync-users
        {type : atpm | dealer}
        {--default-role= : Slug role yang di-assign ke user yang belum punya role}
        {--overwrite-password : Timpa password user yang sudah ada dengan password dari WRS}';

    protected $description = 'Sync user After Sales (ATPM/Dealer) dari WRS ke tabel lokal ms_afsel_*_user';

    private const SOURCES = [
        'atpm' => [
            'table'   => 'master_aftersales.tblatpm_user',
            'columns' => ['kd_atpm_user', 'nm_atpm_user', 'username', 'password', 'fk_atpm_level', 'fk_atpm_department', 'email', 'picture', 'is_active', 'picture_ext', 'tgl_masuk'],
        ],
        'dealer' => [
            'table'   => 'master_aftersales.tbldealer_user',
            'columns' => ['kd_dealer_user', 'nm_dealer_user', 'fk_dealer', 'fk_dealer_level', 'username', 'password', 'email', 'is_active'],
        ],
    ];

    public function handle(AfselRbacService $rbac): int
    {
        $type = $this->argument('type');
        if (!isset(self::SOURCES[$type])) {
            $this->error('Type harus atpm atau dealer.');
            return self::FAILURE;
        }

        $cfg    = $rbac->config($type);
        $source = self::SOURCES[$type];
        $key    = $cfg['user_key'];
        $by     = 'SYSTEM-SYNC';
        $now    = now();

        $defaultRoleId = null;
        if ($slug = $this->option('default-role')) {
            $defaultRoleId = DB::table($rbac->table($type, 'roles'))->where('slug', $slug)->value('id');
            if (!$defaultRoleId) {
                $this->error("Role '{$slug}' tidak ditemukan di {$rbac->table($type, 'roles')}. Jalankan AfselRbacSeeder dulu.");
                return self::FAILURE;
            }
        }

        $inserted = $updated = $assigned = $skipped = 0;

        DB::connection('db_wrs_aftersales')
            ->table($source['table'])
            ->select($source['columns'])
            ->orderBy($key)
            ->chunk(500, function ($rows) use ($cfg, $key, $by, $now, $defaultRoleId, $rbac, $type, &$inserted, &$updated, &$assigned, &$skipped) {
                // key = kode user, value = email_changed_at (email yang diganti user lewat Profile tidak ditimpa)
                $hasEmailFlag = Schema::hasColumn($cfg['user_table'], 'email_changed_at');
                $existing = DB::table($cfg['user_table'])
                    ->whereIn($key, $rows->pluck($key)->all())
                    ->pluck($hasEmailFlag ? 'email_changed_at' : $key, $key)
                    ->map(fn ($v) => $hasEmailFlag ? $v : null);

                foreach ($rows as $row) {
                    // User tanpa username tidak bisa login → dilewati
                    if (trim((string) $row->username) === '') {
                        $skipped++;
                        continue;
                    }

                    $data = collect((array) $row)->except([$key])
                        ->map(fn ($v, $col) => $col === 'password' ? $v : $this->cleanText($v))
                        ->all();
                    $data['is_active'] = (bool) $row->is_active;
                    $data['password']  = (string) $row->password; // NOT NULL; hash kosong = tidak bisa login
                    $data[$cfg['user_name']] = (string) ($data[$cfg['user_name']] ?? '');

                    if ($existing->has($row->$key)) {
                        if (!$this->option('overwrite-password')) {
                            unset($data['password']);
                        }
                        if ($existing->get($row->$key) !== null) {
                            unset($data['email']);
                        }
                        DB::table($cfg['user_table'])->where($key, $row->$key)
                            ->update($data + ['updated_by' => $by, 'date_updated' => $now]);
                        $updated++;
                    } else {
                        DB::table($cfg['user_table'])
                            ->insert([$key => $row->$key] + $data + ['created_by' => $by, 'date_created' => $now]);
                        $inserted++;
                    }

                    if ($defaultRoleId) {
                        $ur = $rbac->table($type, 'user_roles');
                        if (!DB::table($ur)->where($key, $row->$key)->exists()) {
                            DB::table($ur)->insert([
                                $key          => $row->$key,
                                'role_id'     => $defaultRoleId,
                                'assigned_by' => $by,
                                'assigned_at' => $now,
                            ]);
                            $assigned++;
                        }
                    }
                }
            });

        $rbac->flushCache($type);

        $this->info("Sync {$type} selesai: {$inserted} baru, {$updated} diperbarui, {$assigned} user diberi role default, {$skipped} dilewati (tanpa username).");

        return self::SUCCESS;
    }

    /**
     * Data WRS kadang berisi byte non-UTF-8 (mis. \xA0 NBSP Latin-1) → konversi ke UTF-8 valid.
     */
    private function cleanText(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        return trim(str_replace("\xC2\xA0", ' ', $value));
    }
}

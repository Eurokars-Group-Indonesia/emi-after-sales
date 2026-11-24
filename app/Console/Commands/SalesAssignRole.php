<?php

namespace App\Console\Commands;

use App\Services\Sales\SalesRbacService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Assign role RBAC Sales ke user (mis. membuat Super Admin Sales pertama).
 * User dicari di database WRS Sales (tblatpm_user / tbldealer_user).
 *
 *   php artisan sales:assign-role atpm <username> super-admin
 *   php artisan sales:assign-role dealer <username> dealer-user --expires="2026-12-31 23:59:59"
 */
class SalesAssignRole extends Command
{
    protected $signature = 'sales:assign-role
        {type : atpm | dealer}
        {username : Username user WRS Sales}
        {role : Slug role Sales}
        {--expires= : Tanggal kedaluwarsa role (opsional)}';

    protected $description = 'Assign role RBAC Sales ke user (role lain yang sudah dimiliki tetap dipertahankan)';

    public function handle(SalesRbacService $rbac): int
    {
        $type = $this->argument('type');
        if (!$rbac->hasType($type)) {
            $this->error('Type harus atpm atau dealer.');
            return self::FAILURE;
        }

        $cfg   = $rbac->config($type);
        $users = $rbac->userDb()->table($cfg['user_table'])->where('username', $this->argument('username'))->pluck($cfg['user_key']);

        if ($users->count() !== 1) {
            $this->error($users->isEmpty() ? 'User tidak ditemukan di database WRS Sales.' : 'Username terdaftar lebih dari satu.');
            return self::FAILURE;
        }

        $roleId = DB::table($rbac->table($type, 'roles'))->where('slug', $this->argument('role'))->value('id');
        if (!$roleId) {
            $this->error("Role Sales '{$this->argument('role')}' tidak ditemukan.");
            return self::FAILURE;
        }

        $userId = (string) $users->first();

        DB::table($rbac->table($type, 'user_roles'))->updateOrInsert(
            [$cfg['user_key'] => $userId, 'role_id' => $roleId],
            ['assigned_by' => 'SYSTEM-CLI', 'assigned_at' => now(), 'expires_at' => $this->option('expires') ?: null]
        );

        $rbac->flushCache($type);

        $this->info("Role Sales '{$this->argument('role')}' di-assign ke {$this->argument('username')} ({$userId}).");

        return self::SUCCESS;
    }
}

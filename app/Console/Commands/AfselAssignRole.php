<?php

namespace App\Console\Commands;

use App\Services\Afsel\AfselRbacService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Assign role ke user After Sales (mis. membuat Super Admin pertama).
 *
 *   php artisan afsel:assign-role atpm <username> super-admin
 *   php artisan afsel:assign-role dealer <username> admin --expires="2026-12-31 23:59:59"
 */
class AfselAssignRole extends Command
{
    protected $signature = 'afsel:assign-role
        {type : atpm | dealer}
        {username : Username user}
        {role : Slug role}
        {--expires= : Tanggal kedaluwarsa role (opsional)}';

    protected $description = 'Assign role RBAC After Sales ke user (role lain yang sudah dimiliki tetap dipertahankan)';

    public function handle(AfselRbacService $rbac): int
    {
        $type = $this->argument('type');
        if (!isset(AfselRbacService::TYPES[$type])) {
            $this->error('Type harus atpm atau dealer.');
            return self::FAILURE;
        }

        $cfg   = $rbac->config($type);
        $users = DB::table($cfg['user_table'])->where('username', $this->argument('username'))->pluck($cfg['user_key']);

        if ($users->count() !== 1) {
            $this->error($users->isEmpty() ? 'User tidak ditemukan.' : 'Username terdaftar lebih dari satu.');
            return self::FAILURE;
        }

        $roleId = DB::table($rbac->table($type, 'roles'))->where('slug', $this->argument('role'))->value('id');
        if (!$roleId) {
            $this->error("Role '{$this->argument('role')}' tidak ditemukan.");
            return self::FAILURE;
        }

        $userId = (string) $users->first();

        DB::table($rbac->table($type, 'user_roles'))->updateOrInsert(
            [$cfg['user_key'] => $userId, 'role_id' => $roleId],
            ['assigned_by' => 'SYSTEM-CLI', 'assigned_at' => now(), 'expires_at' => $this->option('expires') ?: null]
        );

        $rbac->flushCache($type);

        $this->info("Role '{$this->argument('role')}' di-assign ke {$this->argument('username')} ({$userId}).");

        return self::SUCCESS;
    }
}

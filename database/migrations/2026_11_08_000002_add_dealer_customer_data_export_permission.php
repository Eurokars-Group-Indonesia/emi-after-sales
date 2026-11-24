<?php

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission dealer `customer-data.export` (download Excel Customer Data).
 * Diberikan ke role yang sudah punya `customer-data.view`.
 */
return new class extends Migration
{
    private const BY   = 'SYSTEM-MIGRATION';
    private const SLUG = 'customer-data.export';

    public function up(): void
    {
        $now = now();

        $permId = DB::table('ms_afsel_dealer_permissions')->where('slug', self::SLUG)->value('id');
        if (!$permId) {
            $permId = DB::table('ms_afsel_dealer_permissions')->insertGetId([
                'slug'         => self::SLUG,
                'name'         => 'Download Excel Customer Data',
                'group_name'   => 'Customer',
                'description'  => 'Download data customer dealer sendiri ke Excel (.xlsx).',
                'created_by'   => self::BY,
                'date_created' => $now,
            ]);
        }

        $viewId  = DB::table('ms_afsel_dealer_permissions')->where('slug', 'customer-data.view')->value('id');
        $roleIds = $viewId
            ? DB::table('ms_afsel_dealer_role_permissions')->where('permission_id', $viewId)->pluck('role_id')
            : collect();

        foreach ($roleIds as $roleId) {
            if (!DB::table('ms_afsel_dealer_role_permissions')->where(['role_id' => $roleId, 'permission_id' => $permId])->exists()) {
                DB::table('ms_afsel_dealer_role_permissions')->insert([
                    'role_id' => $roleId, 'permission_id' => $permId, 'created_by' => self::BY, 'date_created' => $now,
                ]);
            }
        }

        app(AfselRbacService::class)->flushCache('dealer');
    }

    public function down(): void
    {
        $permId = DB::table('ms_afsel_dealer_permissions')->where('slug', self::SLUG)->value('id');

        if ($permId) {
            DB::table('ms_afsel_dealer_role_permissions')->where('permission_id', $permId)->delete();
            DB::table('ms_afsel_dealer_permissions')->where('id', $permId)->delete();
        }

        app(AfselRbacService::class)->flushCache('dealer');
    }
};

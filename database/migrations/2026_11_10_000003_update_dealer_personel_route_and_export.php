<?php

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Dealer Personel After Sales:
 *  - URL & nama route pindah: /dealer/aftersales/personel/dealer-personel → /aftersales/dealer/personel/dealer-personel
 *    (route dealer.aftersales.personel.dealer-personel → aftersales.dealer.personel.dealer-personel)
 *  - permission baru personel.export (Download Excel), diberikan ke role yang sudah punya personel.view
 */
return new class extends Migration
{
    private const BY        = 'SYSTEM-MIGRATION';
    private const OLD_ROUTE = 'dealer.aftersales.personel.dealer-personel';
    private const NEW_ROUTE = 'aftersales.dealer.personel.dealer-personel';
    private const SLUG      = 'personel.export';

    public function up(): void
    {
        $now = now();

        DB::table('ms_afsel_dealer_menus')->where('route_name', self::OLD_ROUTE)
            ->update(['route_name' => self::NEW_ROUTE, 'updated_by' => self::BY, 'date_updated' => $now]);

        $permId = DB::table('ms_afsel_dealer_permissions')->where('slug', self::SLUG)->value('id')
            ?: DB::table('ms_afsel_dealer_permissions')->insertGetId([
                'slug' => self::SLUG, 'name' => 'Download Excel Dealer Personel', 'group_name' => 'Personel',
                'description' => 'Download data personel after sales dealer sendiri ke Excel (.xlsx).',
                'created_by' => self::BY, 'date_created' => $now,
            ]);

        $viewId  = DB::table('ms_afsel_dealer_permissions')->where('slug', 'personel.view')->value('id');
        $roleIds = DB::table('ms_afsel_dealer_role_permissions')->where('permission_id', $viewId)->pluck('role_id')
            ->merge(DB::table('ms_afsel_dealer_roles')->where('slug', 'admin')->pluck('id'))
            ->unique();

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

        DB::table('ms_afsel_dealer_menus')->where('route_name', self::NEW_ROUTE)
            ->update(['route_name' => self::OLD_ROUTE, 'updated_by' => self::BY, 'date_updated' => now()]);

        app(AfselRbacService::class)->flushCache('dealer');
    }
};

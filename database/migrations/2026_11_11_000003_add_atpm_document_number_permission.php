<?php

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menu Document Numbering (ATPM) di bawah Administration:
 *  - permission atpm `document-number.view` & `document-number.manage`
 *  - menu `administration.document-number` → route aftersales.atpm.document_number
 *  - permission & menu diberikan ke role ATPM Admin (Super Admin otomatis bypass).
 */
return new class extends Migration
{
    private const BY     = 'SYSTEM-MIGRATION';
    private const ROUTE  = 'aftersales.atpm.document_number';
    private const PARENT = 'administration';
    private const MENU   = 'administration.document-number';

    private const PERMISSIONS = [
        'document-number.view'   => ['Lihat Document Numbering', 'Melihat nomor urut dokumen (kd_customer, kd_kpi, dst).'],
        'document-number.manage' => ['Kelola Document Numbering', 'Menambah table_name & mengubah nomor urut terakhir.'],
    ];

    public function up(): void
    {
        $now = now();

        $permIds = [];
        foreach (self::PERMISSIONS as $slug => [$name, $desc]) {
            $permIds[] = DB::table('ms_afsel_atpm_permissions')->where('slug', $slug)->value('id')
                ?: DB::table('ms_afsel_atpm_permissions')->insertGetId([
                    'slug' => $slug, 'name' => $name, 'group_name' => 'Administration', 'description' => $desc,
                    'created_by' => self::BY, 'date_created' => $now,
                ]);
        }

        $menus = DB::table('ms_afsel_atpm_menus');

        $parentId = (clone $menus)->where('name', self::PARENT)->value('id')
            ?: (clone $menus)->insertGetId([
                'name' => self::PARENT, 'label' => 'Administration', 'icon' => 'bi bi-building',
                'sort_order' => 2, 'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);

        $menuId = (clone $menus)->where('name', self::MENU)->value('id');
        if ($menuId) {
            (clone $menus)->where('id', $menuId)->update([
                'route_name' => self::ROUTE, 'url' => null, 'permission_slug' => 'document-number.view',
                'updated_by' => self::BY, 'date_updated' => $now,
            ]);
        } else {
            $menuId = (clone $menus)->insertGetId([
                'parent_id' => $parentId, 'name' => self::MENU, 'label' => 'Document Numbering',
                'route_name' => self::ROUTE, 'sort_order' => 4, 'permission_slug' => 'document-number.view',
                'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);
        }

        $adminRoleIds = DB::table('ms_afsel_atpm_roles')->where('slug', 'admin')->pluck('id');

        foreach ($adminRoleIds as $roleId) {
            foreach ($permIds as $permId) {
                if (!DB::table('ms_afsel_atpm_role_permissions')->where(['role_id' => $roleId, 'permission_id' => $permId])->exists()) {
                    DB::table('ms_afsel_atpm_role_permissions')->insert([
                        'role_id' => $roleId, 'permission_id' => $permId, 'created_by' => self::BY, 'date_created' => $now,
                    ]);
                }
            }
            foreach ([$parentId, $menuId] as $id) {
                if (!DB::table('ms_afsel_atpm_menu_roles')->where(['role_id' => $roleId, 'menu_id' => $id])->exists()) {
                    DB::table('ms_afsel_atpm_menu_roles')->insert([
                        'role_id' => $roleId, 'menu_id' => $id, 'created_by' => self::BY, 'date_created' => $now,
                    ]);
                }
            }
        }

        app(AfselRbacService::class)->flushCache('atpm');
    }

    public function down(): void
    {
        $permIds = DB::table('ms_afsel_atpm_permissions')->whereIn('slug', array_keys(self::PERMISSIONS))->pluck('id');
        $menuId  = DB::table('ms_afsel_atpm_menus')->where('name', self::MENU)->value('id');

        DB::table('ms_afsel_atpm_role_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('ms_afsel_atpm_permissions')->whereIn('id', $permIds)->delete();

        if ($menuId) {
            DB::table('ms_afsel_atpm_menu_roles')->where('menu_id', $menuId)->delete();
            if (DB::getSchemaBuilder()->hasTable('ms_afsel_atpm_menu_users')) {
                DB::table('ms_afsel_atpm_menu_users')->where('menu_id', $menuId)->delete();
            }
            DB::table('ms_afsel_atpm_menus')->where('id', $menuId)->delete();
        }

        app(AfselRbacService::class)->flushCache('atpm');
    }
};

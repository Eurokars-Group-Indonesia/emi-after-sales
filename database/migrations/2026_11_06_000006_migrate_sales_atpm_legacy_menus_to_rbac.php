<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Pindahkan menu Sales ATPM lama ke tabel RBAC Sales:
 *
 *   ms_sales_atpm_master_menu  → ms_sales_atpm_menus       (id dipertahankan)
 *   tr_sales_atpm_user_menu    → ms_sales_atpm_menu_users  (akses per user, is_granted = 1)
 *
 * Tabel lama boleh sudah di-rename dengan akhiran "_" (ms_sales_atpm_master_menu_, tr_sales_atpm_user_menu_).
 * Setiap user yang punya menu lama diberi role default "user" agar punya role aktif saat login Sales.
 * Idempotent: data yang sudah ada tidak ditimpa.
 */
return new class extends Migration
{
    private const BY = 'SYSTEM-MIGRATION';

    public function up(): void
    {
        $now = now();

        // ---------- roles ----------
        foreach ([
            ['slug' => 'super-admin', 'name' => 'Super Admin', 'level' => 1,  'is_system' => true,  'description' => 'Akses penuh ke seluruh menu Sales ATPM (bypass RBAC).'],
            ['slug' => 'user',        'name' => 'User',        'level' => 50, 'is_system' => false, 'description' => 'User Sales ATPM. Menu diatur per user (Administration > User Menu).'],
        ] as $role) {
            if (!DB::table('ms_sales_atpm_roles')->where('slug', $role['slug'])->exists()) {
                DB::table('ms_sales_atpm_roles')->insert($role + ['is_active' => true, 'created_by' => self::BY, 'date_created' => $now]);
            }
        }
        $defaultRoleId = (int) DB::table('ms_sales_atpm_roles')->where('slug', 'user')->value('id');

        // ---------- menus ----------
        $menuTable = $this->legacyTable('ms_sales_atpm_master_menu');
        if ($menuTable) {
            foreach (DB::table($menuTable)->orderBy('id')->get() as $m) {
                if (DB::table('ms_sales_atpm_menus')->where('id', $m->id)->exists()) {
                    continue;
                }

                DB::table('ms_sales_atpm_menus')->insert([
                    'id'           => $m->id,
                    'parent_id'    => $m->parent_id,
                    'name'         => $this->uniqueName($m),
                    'label'        => $m->title,
                    'route_name'   => $m->route ?: null,
                    'url'          => null,
                    'icon'         => $m->icon ?: null,
                    'sort_order'   => (int) $m->order,
                    'is_active'    => (bool) $m->is_active,
                    'created_by'   => self::BY,
                    'date_created' => $now,
                ]);
            }
        }

        // ---------- akses menu per user ----------
        $userMenuTable = $this->legacyTable('tr_sales_atpm_user_menu');
        if ($userMenuTable) {
            $menuIds = DB::table('ms_sales_atpm_menus')->pluck('id')->map(fn ($id) => (int) $id)->flip();

            $rows = DB::table($userMenuTable)->where('is_active', 1)->get(['fk_kd_atpm_user', 'fk_sales_atpm_master_menu']);

            foreach ($rows as $row) {
                $menuId = (int) $row->fk_sales_atpm_master_menu;
                if (!isset($menuIds[$menuId])) {
                    continue; // menu sudah dihapus dari master
                }

                DB::table('ms_sales_atpm_menu_users')->insertOrIgnore([
                    'menu_id'      => $menuId,
                    'kd_atpm_user' => $row->fk_kd_atpm_user,
                    'is_granted'   => true,
                    'created_by'   => self::BY,
                    'date_created' => $now,
                ]);
            }

            foreach ($rows->pluck('fk_kd_atpm_user')->unique() as $kdUser) {
                DB::table('ms_sales_atpm_user_roles')->insertOrIgnore([
                    'kd_atpm_user' => $kdUser,
                    'role_id'      => $defaultRoleId,
                    'assigned_by'  => self::BY,
                    'assigned_at'  => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['menu_users', 'user_roles', 'menu_roles', 'menus', 'roles'] as $t) {
            DB::table('ms_sales_atpm_' . $t)->delete();
        }
    }

    /** Nama tabel lama: asli atau yang sudah di-rename dengan akhiran "_". */
    private function legacyTable(string $name): ?string
    {
        foreach ([$name, $name . '_'] as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    private function uniqueName(object $m): string
    {
        $base = $m->route ?: Str::slug($m->title);
        $name = Str::limit($base, 90, '');

        return DB::table('ms_sales_atpm_menus')->where('name', $name)->exists()
            ? Str::limit($base, 85, '') . '-' . $m->id
            : $name;
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Samakan menu RBAC After Sales ATPM (ms_afsel_atpm_menus) dengan menu lama ms_menu_aftersales_atpm
 * (boleh sudah di-rename dengan akhiran "_").
 *
 *  - Menu lama yang belum ada di RBAC → dibuat (hierarki mengikuti menu lama), lalu diberikan ke role "admin".
 *  - Menu RBAC yang lepas dari parent-nya (parent_id NULL, padahal di menu lama punya parent) → dikembalikan ke parent.
 *  - Menu yang sudah punya parent / diatur admin lewat halaman Access Control tidak diubah.
 */
return new class extends Migration
{
    private const BY = 'SYSTEM-MIGRATION';
    private const T  = 'ms_afsel_atpm_menus';

    /** Permission menu per route (sama dengan AfselRbacSeeder & middleware route). */
    private const ROUTE_PERMISSION = [
        'aftersales.atpm.atpm_user'                        => 'atpm-user.view',
        'aftersales.atpm.model_other'                      => 'model-other.view',
        'aftersales.atpm.utility.sync_index'               => 'sync-monitoring.view',
        'aftersales.atpm.vehicle_service_history'          => 'service-history.view',
        'aftersales.atpm.report.service-retention'         => 'report-retention.view',
        'aftersales.atpm.report.service-retention-old'     => 'report-retention-old.view',
    ];

    public function up(): void
    {
        $legacy = collect(['ms_menu_aftersales_atpm', 'ms_menu_aftersales_atpm_'])->first(fn ($t) => Schema::hasTable($t));
        if (!$legacy || !Schema::hasTable(self::T)) {
            return;
        }

        $now     = now();
        $rows    = DB::table($legacy)->where('is_active', 1)->orderBy('id')->get()->keyBy('id');
        $map     = []; // legacy id => rbac id
        $created = [];

        // Parent selalu diproses lebih dulu dari child-nya
        $pending = $rows->all();
        $guard = 0;
        while ($pending && $guard++ < 20) {
            foreach ($pending as $id => $row) {
                if ($row->parent_id && !isset($map[$row->parent_id]) && isset($rows[$row->parent_id])) {
                    continue;
                }

                $parentId = $row->parent_id ? ($map[$row->parent_id] ?? null) : null;
                $menu     = $this->findMenu($row, $parentId);

                if ($menu) {
                    // Kembalikan menu yatim ke parent seperti di menu lama
                    if ($parentId && $menu->parent_id === null) {
                        DB::table(self::T)->where('id', $menu->id)->update([
                            'parent_id' => $parentId, 'sort_order' => (int) $row->order,
                            'updated_by' => self::BY, 'date_updated' => $now,
                        ]);
                    }
                    $map[$id] = (int) $menu->id;
                } else {
                    $map[$id] = $created[] = (int) DB::table(self::T)->insertGetId([
                        'parent_id'       => $parentId,
                        'name'            => $this->uniqueName($row, $parentId),
                        'label'           => $row->title,
                        'route_name'      => $row->route ?: null,
                        'icon'            => $this->icon($row->icon),
                        'sort_order'      => (int) $row->order,
                        'permission_slug' => self::ROUTE_PERMISSION[$row->route] ?? null,
                        'is_active'       => true,
                        'created_by'      => self::BY,
                        'date_created'    => $now,
                    ]);
                }

                unset($pending[$id]);
            }
        }

        // Menu baru → role admin (super admin sudah bypass semua menu)
        $adminRoleId = DB::table('ms_afsel_atpm_roles')->where('slug', 'admin')->value('id');
        if ($adminRoleId) {
            foreach ($created as $menuId) {
                DB::table('ms_afsel_atpm_menu_roles')->insertOrIgnore([
                    'menu_id' => $menuId, 'role_id' => $adminRoleId, 'created_by' => self::BY, 'date_created' => $now,
                ]);
            }
        }

        // Invalidate cache menu RBAC
        if (class_exists(\App\Services\Afsel\AfselRbacService::class)) {
            app(\App\Services\Afsel\AfselRbacService::class)->flushCache('atpm');
        }
    }

    public function down(): void
    {
        // Perbaikan data, tidak di-rollback.
    }

    private function findMenu(object $row, ?int $parentId): ?object
    {
        if ($row->route) {
            return DB::table(self::T)->where('route_name', $row->route)->orderBy('id')->first();
        }

        // Menu grup (tanpa route): cocokkan label di parent yang sama, atau yang masih yatim
        return DB::table(self::T)
            ->whereNull('route_name')
            ->where('label', $row->title)
            ->where(fn ($q) => $parentId ? $q->where('parent_id', $parentId)->orWhereNull('parent_id') : $q->whereNull('parent_id'))
            ->orderBy('id')
            ->first();
    }

    private function uniqueName(object $row, ?int $parentId): string
    {
        $parentName = $parentId ? DB::table(self::T)->where('id', $parentId)->value('name') : null;
        $base = ltrim(($parentName ? $parentName . '.' : '') . Str::slug($row->title), '.');
        $name = $base;
        $i = 2;
        while (DB::table(self::T)->where('name', $name)->exists()) {
            $name = $base . '-' . $i++;
        }

        return $name;
    }

    private function icon(?string $icon): ?string
    {
        if (!$icon) {
            return null;
        }

        return str_starts_with($icon, 'bi-') ? 'bi ' . $icon : $icon;
    }
};

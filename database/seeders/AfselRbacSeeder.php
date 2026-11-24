<?php

namespace Database\Seeders;

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder RBAC WRS Connect - After Sales (ATPM & Dealer).
 * Idempotent: aman dijalankan berulang. Hanya MENAMBAH data yang belum ada (berdasarkan slug / name),
 * data yang sudah ada tidak ditimpa.
 *
 *   php artisan db:seed --class=AfselRbacSeeder
 *
 * Menu diambil dari menu lama (ms_menu_aftersales_atpm & menu_dealer).
 * Mapping role → permission & menu diisi lengkap saat role baru dibuat.
 * Untuk role lama dengan definisi '*', hanya permission / menu BARU yang ditambahkan.
 */
class AfselRbacSeeder extends Seeder
{
    private const BY = 'SYSTEM-SEEDER';

    public function run(): void
    {
        $this->seedType('atpm', $this->atpmDefinition());
        $this->seedType('dealer', $this->dealerDefinition());

        app(AfselRbacService::class)->flushCache();
    }

    private function seedType(string $type, array $def): void
    {
        $rbac = app(AfselRbacService::class);
        $now  = now();
        $newPermIds = [];
        $newMenuIds = [];

        // ---------- permissions ----------
        foreach ($def['permissions'] as [$slug, $name, $group]) {
            if ($id = $this->insertIfMissing($rbac->table($type, 'permissions'), ['slug' => $slug], [
                'name' => $name, 'group_name' => $group,
            ], $now)) {
                $newPermIds[] = $id;
            }
        }
        $permIds = DB::table($rbac->table($type, 'permissions'))->pluck('id', 'slug');

        // ---------- menus (parent dulu, lalu child) ----------
        foreach ($def['menus'] as $menu) {
            $parentId = !empty($menu['parent'])
                ? DB::table($rbac->table($type, 'menus'))->where('name', $menu['parent'])->value('id')
                : null;

            if ($id = $this->insertIfMissing($rbac->table($type, 'menus'), ['name' => $menu['name']], [
                'parent_id'       => $parentId,
                'label'           => $menu['label'],
                'route_name'      => $menu['route'] ?? null,
                'icon'            => $menu['icon'] ?? null,
                'sort_order'      => $menu['order'],
                'permission_slug' => $menu['permission'] ?? null,
                'is_active'       => true,
            ], $now)) {
                $newMenuIds[] = $id;
            }
        }
        $menuIds = DB::table($rbac->table($type, 'menus'))->pluck('id', 'name');

        // ---------- roles ----------
        foreach ($def['roles'] as $role) {
            $table  = $rbac->table($type, 'roles');
            $roleId = $this->insertIfMissing($table, ['slug' => $role['slug']], [
                'name'        => $role['name'],
                'description' => $role['description'],
                'level'       => $role['level'],
                'is_system'   => $role['is_system'] ?? false,
                'is_active'   => true,
            ], $now);

            if ($roleId) {
                // Role baru → isi mapping lengkap
                $perms = $role['permissions'] === '*' ? $permIds->values()->all()
                    : collect($role['permissions'])->map(fn ($s) => $permIds[$s] ?? null)->filter()->values()->all();
                $menus = $role['menus'] === '*' ? $menuIds->values()->all()
                    : collect($role['menus'])->map(fn ($n) => $menuIds[$n] ?? null)->filter()->values()->all();

                $rbac->syncRolePermissions($type, $roleId, $perms, self::BY);
                $rbac->syncRoleMenus($type, $roleId, $menus, self::BY);
                continue;
            }

            // Role lama dengan definisi '*' → hanya TAMBAHKAN permission / menu yang baru dibuat
            $roleId = (int) DB::table($table)->where('slug', $role['slug'])->value('id');
            if ($role['permissions'] === '*') {
                $this->attach($rbac->table($type, 'role_permissions'), $roleId, 'permission_id', $newPermIds, $now);
            }
            if ($role['menus'] === '*') {
                $this->attach($rbac->table($type, 'menu_roles'), $roleId, 'menu_id', $newMenuIds, $now);
            }
        }
    }

    /**
     * Insert jika belum ada. Data yang sudah ada TIDAK ditimpa (bisa sudah diubah admin lewat halaman Access Control).
     * @return int|null id baru, atau null jika sudah ada
     */
    private function insertIfMissing(string $table, array $where, array $values, $now): ?int
    {
        if (DB::table($table)->where($where)->exists()) {
            return null;
        }

        return (int) DB::table($table)->insertGetId($where + $values + ['created_by' => self::BY, 'date_created' => $now]);
    }

    private function attach(string $table, int $roleId, string $col, array $ids, $now): void
    {
        foreach ($ids as $id) {
            DB::table($table)->insertOrIgnore([
                'role_id' => $roleId, $col => $id, 'created_by' => self::BY, 'date_created' => $now,
            ]);
        }
    }

    // =====================================================================
    // DEFINISI ATPM
    // =====================================================================
    private function atpmDefinition(): array
    {
        return [
            'permissions' => [
                // [slug, nama, group]
                ['atpm-user.view',            'Lihat User ATPM',              'Administration'],
                ['atpm-user.sync',            'Sync User ATPM dari WRS',      'Administration'],
                ['atpm-user.manage-access',   'Atur Menu & Permission User',  'Administration'],
                ['model-other.view',          'Lihat Model Other',            'Administration'],
                ['model-other.create',        'Tambah Model Other',           'Administration'],
                ['model-other.edit',          'Ubah Model Other',             'Administration'],
                ['sync-monitoring.view',      'Lihat Sync Monitoring',        'Administration'],
                ['service-history.view',      'Lihat Service History',        'Vehicle'],
                ['report-retention.view',     'Lihat Retention Report',       'Reports'],
                ['report-retention-old.view', 'Lihat Retention Report Old',   'Reports'],
                ['level-personel.view',       'Lihat Level Personel After Sales',  'Personel After Sales'],
                ['level-personel.create',     'Tambah Level Personel After Sales', 'Personel After Sales'],
                ['level-personel.edit',       'Ubah Level Personel After Sales',   'Personel After Sales'],
                ['dealer-personel.view',      'Lihat Dealer Personel After Sales', 'Personel After Sales'],
                ['dealer-personel.sync',      'Sync Dealer Personel dari WRS',     'Personel After Sales'],
                ['dealer-personel.edit',      'Ubah Active & Black List EMI Dealer Personel', 'Personel After Sales'],
                ['category-vehicle-1.view',   'Lihat Cat Service Vehicle 1',  'General Setting'],
                ['category-vehicle-1.create', 'Tambah Cat Service Vehicle 1', 'General Setting'],
                ['category-vehicle-1.edit',   'Ubah Cat Service Vehicle 1',   'General Setting'],
                ['category-vehicle-1.delete', 'Hapus Cat Service Vehicle 1',  'General Setting'],
                ['category-vehicle-2.view',   'Lihat Cat Service Vehicle 2',  'General Setting'],
                ['category-vehicle-2.create', 'Tambah Cat Service Vehicle 2', 'General Setting'],
                ['category-vehicle-2.edit',   'Ubah Cat Service Vehicle 2',   'General Setting'],
                ['category-vehicle-2.delete', 'Hapus Cat Service Vehicle 2',  'General Setting'],
                ['rbac.role.manage',          'Kelola Role',                  'RBAC'],
                ['rbac.permission.manage',    'Kelola Permission',            'RBAC'],
                ['rbac.menu.manage',          'Kelola Menu',                  'RBAC'],
                ['rbac.user-role.manage',     'Kelola Role User',             'RBAC'],
                ['rbac.user.manage',          'Tambah & Ubah Data User',      'RBAC'],
            ],

            // Diambil dari ms_menu_aftersales_atpm
            'menus' => [
                ['name' => 'home',                 'label' => 'Home',                 'route' => 'aftersales.atpm.home',                       'icon' => 'bi bi-house-fill',     'order' => 1],
                ['name' => 'administration',       'label' => 'Administration',                                                                'icon' => 'bi bi-building',       'order' => 2],
                ['name' => 'administration.atpm',  'label' => 'ATPM',                 'parent' => 'administration',                            'icon' => 'bi bi-people',         'order' => 1],
                ['name' => 'administration.atpm.user', 'label' => 'User',             'parent' => 'administration.atpm', 'route' => 'aftersales.atpm.atpm_user', 'order' => 1, 'permission' => 'atpm-user.view'],
                ['name' => 'administration.model-other', 'label' => 'Model Other',    'parent' => 'administration', 'route' => 'aftersales.atpm.model_other', 'order' => 2, 'permission' => 'model-other.view'],
                ['name' => 'administration.sync-monitoring', 'label' => 'Sync Monitoring', 'parent' => 'administration', 'route' => 'aftersales.atpm.utility.sync_index', 'order' => 3, 'permission' => 'sync-monitoring.view'],
                ['name' => 'vehicle',              'label' => 'Vehicle',                                                                       'icon' => 'bi bi-car-front',      'order' => 3],
                ['name' => 'vehicle.service-history', 'label' => 'Service History',   'parent' => 'vehicle', 'route' => 'aftersales.atpm.vehicle_service_history', 'order' => 1, 'permission' => 'service-history.view'],
                ['name' => 'reports',              'label' => 'Reports',                                                                       'icon' => 'bi bi-bar-chart-fill', 'order' => 4],
                ['name' => 'reports.retention-old', 'label' => 'Retention Report Old', 'parent' => 'reports', 'route' => 'aftersales.atpm.report.service-retention-old', 'order' => 1, 'permission' => 'report-retention-old.view'],
                ['name' => 'reports.retention',    'label' => 'Retention Report',     'parent' => 'reports', 'route' => 'aftersales.atpm.report.service-retention', 'order' => 2, 'permission' => 'report-retention.view'],

                // Personel After Sales
                ['name' => 'personel-after-sales',      'label' => 'Personel After Sales',                                                         'icon' => 'bi bi-person-badge-fill', 'order' => 5],
                ['name' => 'level-personel-aftersales', 'label' => 'Level Personel After Sales', 'parent' => 'personel-after-sales', 'route' => 'aftersales.atpm.level_personel', 'order' => 1, 'permission' => 'level-personel.view'],
                ['name' => 'dealer-personel-aftersales', 'label' => 'Dealer Personel After Sales', 'parent' => 'personel-after-sales', 'route' => 'aftersales.atpm.dealer_personel', 'order' => 2, 'permission' => 'dealer-personel.view'],

                // General Setting > Category Service
                ['name' => 'afsel.atpm.general-setting',            'label' => 'General Setting',                                                       'icon' => 'bi bi-gear',  'order' => 3],
                ['name' => 'afsel.atpm.category-service',           'label' => 'Category Service',      'parent' => 'afsel.atpm.general-setting',      'icon' => 'bi bi-tools', 'order' => 1],
                ['name' => 'afsel.atpm.category-service-vehicle-1', 'label' => 'Cat Service Vehicle 1', 'parent' => 'afsel.atpm.category-service', 'route' => 'aftersales.atpm.category_vehicle_1', 'order' => 1, 'permission' => 'category-vehicle-1.view'],
                ['name' => 'afsel.atpm.category-service-vehicle-2', 'label' => 'Cat Service Vehicle 2', 'parent' => 'afsel.atpm.category-service', 'route' => 'aftersales.atpm.category_vehicle_2', 'order' => 2, 'permission' => 'category-vehicle-2.view'],

                // Access Control (RBAC) — kelola akses ATPM & Dealer
                ['name' => 'access-control',             'label' => 'Access Control',                                                                    'icon' => 'bi bi-shield-lock', 'order' => 9],
                ['name' => 'access-control.users',       'label' => 'User Access', 'parent' => 'access-control', 'route' => 'aftersales.atpm.access.users',       'order' => 1, 'permission' => 'rbac.user-role.manage'],
                ['name' => 'access-control.roles',       'label' => 'Role',        'parent' => 'access-control', 'route' => 'aftersales.atpm.access.roles',       'order' => 2, 'permission' => 'rbac.role.manage'],
                ['name' => 'access-control.permissions', 'label' => 'Permission',  'parent' => 'access-control', 'route' => 'aftersales.atpm.access.permissions', 'order' => 3, 'permission' => 'rbac.permission.manage'],
                ['name' => 'access-control.menus',       'label' => 'Menu',        'parent' => 'access-control', 'route' => 'aftersales.atpm.access.menus',       'order' => 4, 'permission' => 'rbac.menu.manage'],
            ],

            'roles' => [
                [
                    'slug' => 'super-admin', 'name' => 'Super Admin', 'level' => 1, 'is_system' => true,
                    'description' => 'Akses penuh ke seluruh menu & fitur (bypass RBAC).',
                    'permissions' => '*', 'menus' => '*',
                ],
                [
                    'slug' => 'admin', 'name' => 'Admin', 'level' => 10,
                    'description' => 'Administrator After Sales ATPM.',
                    'permissions' => '*', 'menus' => '*',
                ],
                [
                    'slug' => 'user', 'name' => 'User', 'level' => 50,
                    'description' => 'User ATPM: melihat data kendaraan & report.',
                    'permissions' => ['service-history.view', 'report-retention.view', 'report-retention-old.view'],
                    'menus' => ['home', 'vehicle', 'vehicle.service-history', 'reports', 'reports.retention', 'reports.retention-old'],
                ],
            ],
        ];
    }

    // =====================================================================
    // DEFINISI DEALER
    // =====================================================================
    private function dealerDefinition(): array
    {
        return [
            'permissions' => [
                ['report-retention.view', 'Lihat Retention Report', 'Reports'],
                ['customer-data.view',    'Lihat Customer Data',    'Customer'],
                ['customer-data.export',  'Download Excel Customer Data', 'Customer'],
                ['service-data.view',     'Lihat Data Service',     'Service'],
                ['service-data.export',   'Download Excel Data Service', 'Service'],
                ['personel.view',         'Lihat Dealer Personel',  'Personel'],
                ['personel.create',       'Tambah Dealer Personel', 'Personel'],
                ['personel.edit',         'Ubah Dealer Personel',   'Personel'],
                ['personel.status',       'Ubah Status & Data Keluar Personel', 'Personel'],
                ['personel.export',       'Download Excel Dealer Personel', 'Personel'],
            ],

            // Diambil dari menu_dealer
            'menus' => [
                ['name' => 'home',              'label' => 'Home',             'route' => 'aftersales.dealer.home',          'icon' => 'bi bi-house-fill',     'order' => 1],
                ['name' => 'customer',          'label' => 'Customer Data',                                                  'icon' => 'bi bi-people',         'order' => 2],
                ['name' => 'customer.index',    'label' => 'Customer',         'parent' => 'customer', 'route' => 'aftersales.dealer.customer.customer-data', 'order' => 1, 'permission' => 'customer-data.view'],
                ['name' => 'service',           'label' => 'Service',                                                        'icon' => 'bi bi-tools',          'order' => 2],
                ['name' => 'service.index',     'label' => 'Data Service',     'parent' => 'service', 'route' => 'aftersales.dealer.service.service-data', 'order' => 2, 'permission' => 'service-data.view'],
                ['name' => 'afsel.dealer.personel-after-sales',        'label' => 'Personel After Sales', 'icon' => 'bi bi-person-badge', 'order' => 4],
                ['name' => 'afsel.dealer.dealer-personel-after-sales', 'label' => 'Dealer Personel After Sales', 'parent' => 'afsel.dealer.personel-after-sales', 'route' => 'aftersales.dealer.personel.dealer-personel', 'order' => 1, 'permission' => 'personel.view'],
                ['name' => 'reports',           'label' => 'Reports',                                                        'icon' => 'bi bi-bar-chart-fill', 'order' => 3],
                ['name' => 'reports.retention', 'label' => 'Retention Report', 'parent' => 'reports', 'route' => 'dealer.report.service-retention', 'order' => 1, 'permission' => 'report-retention.view'],
            ],

            'roles' => [
                [
                    'slug' => 'admin', 'name' => 'Dealer Admin', 'level' => 10,
                    'description' => 'Administrator dealer.',
                    'permissions' => '*', 'menus' => '*',
                ],
                [
                    'slug' => 'user', 'name' => 'Dealer User', 'level' => 50,
                    'description' => 'User dealer.',
                    'permissions' => ['report-retention.view', 'customer-data.view', 'customer-data.export', 'service-data.view', 'service-data.export', 'personel.view', 'personel.create', 'personel.edit', 'personel.status', 'personel.export'],
                    'menus' => ['home', 'customer', 'customer.index', 'service', 'service.index', 'afsel.dealer.personel-after-sales', 'afsel.dealer.dealer-personel-after-sales', 'reports', 'reports.retention'],
                ],
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Services\Sales\SalesRbacService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Menu fitur Sales ATPM di tabel RBAC (ms_sales_atpm_menus).
 *
 * Migration 2026_11_06_000006 hanya MEMINDAHKAN menu dari tabel lama ms_sales_atpm_master_menu.
 * Di database baru (tabel lama kosong) menu fitur tidak pernah terbentuk, sehingga sidebar hanya
 * berisi Administration > Access Control. Seeder ini mengisi menu fitur tersebut.
 *
 * Idempotent & aman untuk database yang sudah berisi menu hasil migrasi:
 *  - menu grup dicari berdasarkan kolom `name` (slug judul, sama seperti hasil migrasi menu lama)
 *  - menu halaman dilewati jika route_name-nya sudah ada
 *  - data yang sudah ada tidak ditimpa
 *
 * Jalankan: php artisan db:seed --class=SalesAtpmRbacMenuSeeder
 */
class SalesAtpmRbacMenuSeeder extends Seeder
{
    private const TABLE = 'ms_sales_atpm_menus';
    private const BY    = 'SYSTEM-SEEDER';

    /**
     * Struktur menu. Grup: tanpa 'route'. Halaman: 'route' + 'permission' (slug middleware sales.permission).
     */
    private function menus(): array
    {
        return [
            ['name' => 'sales.atpm.home', 'label' => 'Home', 'route' => 'sales.atpm.home', 'icon' => 'bi bi-house-fill', 'order' => 1],

            ['name' => 'system-setup', 'label' => 'System Setup', 'icon' => 'bi bi-gear', 'order' => 2, 'children' => [
                ['name' => 'sales.atpm.system_setup.config', 'label' => 'Config', 'route' => 'sales.atpm.system_setup.config', 'permission' => 'config.view', 'order' => 1],
            ]],

            // Administration > Access Control dibuat oleh migration 2026_11_20_000002.
            // Administration > ATPM > User sudah tidak dipakai (dihapus migration 2026_11_24_000001).

            ['name' => 'email-scheduler', 'label' => 'Email Scheduler', 'icon' => 'bi bi-envelope', 'order' => 4, 'children' => [
                ['name' => 'sales.atpm.email_scheduler.delivery_ckd', 'label' => 'Delivery CKD', 'route' => 'sales.atpm.email_scheduler.delivery_ckd', 'permission' => 'email-scheduler-ckd.view', 'order' => 1],
            ]],

            ['name' => 'report', 'label' => 'Report', 'icon' => 'bi bi-receipt', 'order' => 5, 'children' => [
                ['name' => 'sales-person', 'label' => 'Sales Person', 'icon' => 'bi bi-people', 'order' => 1, 'children' => [
                    ['name' => 'sales.atpm.report.sales_person_history', 'label' => 'Sales Person History Report', 'route' => 'sales.atpm.report.sales_person_history', 'permission' => 'report.sales-person-history.view', 'order' => 1],
                    ['name' => 'sales.atpm.report.sales_person_productivity', 'label' => 'Sales Person Productivity Report', 'route' => 'sales.atpm.report.sales_person_productivity', 'permission' => 'report.sales-person-productivity.view', 'order' => 2],
                    ['name' => 'sales.atpm.report.sales_person_list', 'label' => 'Sales Person List Report', 'route' => 'sales.atpm.report.sales_person_list', 'permission' => 'report.sales-person-list.view', 'order' => 3],
                ]],
            ]],
        ];
    }

    public function run(SalesRbacService $rbac): void
    {
        $created = $this->seed($this->menus(), null);

        $rbac->flushCache('atpm');

        $this->command?->info("SalesAtpmRbacMenuSeeder: {$created} menu baru ditambahkan.");
    }

    private function seed(array $items, ?int $parentId): int
    {
        $created = 0;

        foreach ($items as $item) {
            $route = $item['route'] ?? null;

            $id = $route
                ? DB::table(self::TABLE)->where('route_name', $route)->value('id')
                : null;
            $id ??= DB::table(self::TABLE)->where('name', $item['name'])->value('id');

            if (!$id) {
                $id = DB::table(self::TABLE)->insertGetId([
                    'parent_id'       => $parentId,
                    'name'            => $item['name'],
                    'label'           => $item['label'],
                    'route_name'      => $route,
                    'url'             => null,
                    'icon'            => $item['icon'] ?? null,
                    'sort_order'      => $item['order'],
                    'permission_slug' => $item['permission'] ?? null,
                    'is_active'       => true,
                    'created_by'      => self::BY,
                    'date_created'    => now(),
                ]);
                $created++;
            }

            if (!empty($item['children'])) {
                $created += $this->seed($item['children'], (int) $id);
            }
        }

        return $created;
    }
}

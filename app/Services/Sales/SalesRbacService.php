<?php

namespace App\Services\Sales;

use App\Models\Sales\SalesAtpmRbacMenu;
use App\Models\Sales\SalesAtpmRbacRole;
use App\Models\Sales\SalesDealerRbacMenu;
use App\Models\Sales\SalesDealerRbacRole;
use App\Services\Rbac\RbacEngine;
use Illuminate\Support\Facades\DB;

/**
 * RBAC WRS Connect - Sales (ATPM & Dealer).
 *
 * Terpisah total dari RBAC After Sales (AfselRbacService):
 *  - tabel       : ms_sales_atpm_* / ms_sales_dealer_*  (After Sales: ms_afsel_*)
 *  - session key : user.sales_role_id                    (After Sales: user.afsel_role_id)
 *  - cache       : sales_rbac_*                          (After Sales: afsel_rbac_*)
 *
 * Akun user Sales tetap milik database WRS Sales (tblatpm_user / tbldealer_user, read only).
 * RBAC lokal hanya menyimpan role, permission, dan menu per kode user (kd_atpm_user / kd_dealer_user).
 */
class SalesRbacService extends RbacEngine
{
    /** Session key role aktif Sales */
    public const SESSION_ROLE_KEY = 'user.sales_role_id';

    protected const CACHE_PREFIX = 'sales_rbac';

    public const TYPES = [
        'atpm' => [
            'prefix'     => 'ms_sales_atpm_',
            'user_table' => 'tblatpm_user',      // di koneksi WRS Sales (config sales.auth_connection)
            'user_key'   => 'kd_atpm_user',
            'user_name'  => 'nm_atpm_user',
            'role_model' => SalesAtpmRbacRole::class,
            'menu_model' => SalesAtpmRbacMenu::class,
            'home_route' => 'sales.atpm.home',
            'app'        => 'sales',
            'label'      => 'ATPM',
        ],
        'dealer' => [
            'prefix'     => 'ms_sales_dealer_',
            'user_table' => 'tbldealer_user',    // di koneksi WRS Sales (config sales.auth_connection)
            'user_key'   => 'kd_dealer_user',
            'user_name'  => 'nm_dealer_user',
            'role_model' => SalesDealerRbacRole::class,
            'menu_model' => SalesDealerRbacMenu::class,
            'home_route' => 'sales.dealer.home',
            'app'        => 'sales',
            'label'      => 'Dealer',
        ],
    ];

    protected function cacheTtl(): int
    {
        return (int) config('sales.rbac_cache_ttl', 3600);
    }

    /** Koneksi database WRS Sales (sumber akun user Sales). */
    public function userDb(): \Illuminate\Database\Connection
    {
        return DB::connection(config('sales.auth_connection', 'db_wrs_sales'));
    }

    /**
     * Pastikan user punya role. Jika belum dan tipe ini punya role default (config sales.default_role.{type}),
     * role default otomatis di-assign (dipakai saat login pertama user Dealer).
     */
    public function ensureDefaultRole(string $type, string $userId): void
    {
        if ($this->getAssignedRoles($type, $userId)->isNotEmpty()) {
            return;
        }

        $slug = config("sales.default_role.{$type}");
        if (!$slug) {
            return;
        }

        $roleId = DB::table($this->table($type, 'roles'))->where('slug', $slug)->where('is_active', true)->value('id');
        if (!$roleId) {
            return;
        }

        DB::table($this->table($type, 'user_roles'))->insertOrIgnore([
            $this->config($type)['user_key'] => $userId,
            'role_id'     => $roleId,
            'assigned_by' => 'SYSTEM-DEFAULT-ROLE',
            'assigned_at' => now(),
        ]);

        // Cukup reset cache role per-request; cache menu memakai key per role sehingga tidak perlu di-flush
        $this->rolesCache = [];
    }
}

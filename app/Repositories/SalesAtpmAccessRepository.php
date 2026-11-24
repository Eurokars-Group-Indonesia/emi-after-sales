<?php

namespace App\Repositories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\Sales\SalesRbacService;

interface SalesAtpmAccessInterface
{
    /* GET */
    public function findUserMenuBykd($kd_atpm_user);    // return first();
    public function findAllMasterMenu();    // return get();
    public function findAllDatatableMasterMenu($srcText);  // return get();
    public function findUserPermissionBykd($kd_atpm_user); // return get();

    public function findAllMasterPermission(); // return get();


    /* POST */
    public function updateUserMenuById($dto); // return callback,  
    public function updateUserPermissionById($dto); // return callback,
}

class SalesAtpmAccessRepository implements SalesAtpmAccessInterface
{
    /*
     * Menu Sales ATPM memakai tabel RBAC Sales (lihat SalesRbacService tipe 'atpm'):
     *   ms_sales_atpm_menus       (pengganti ms_sales_atpm_master_menu)
     *   ms_sales_atpm_menu_users  (pengganti tr_sales_atpm_user_menu, override per user)
     *   ms_sales_atpm_menu_roles  (menu per role)
     * Kolom di-alias ke nama lama (title, route, order, fk_sales_atpm_master_menu) supaya view lama tetap jalan.
     */
    private const RBAC_TYPE = 'atpm';

    private function rbac(): SalesRbacService
    {
        return app(SalesRbacService::class);
    }

    private function menuQuery()
    {
        return DB::table('ms_sales_atpm_menus')->select(
            'id',
            DB::raw('label as title'),
            DB::raw('route_name as route'),
            'icon',
            'parent_id',
            DB::raw('sort_order as `order`'),
            'is_active'
        );
    }

    /** Menu ID dari semua role yang dimiliki user. */
    private function roleMenuIds(string $kdAtpmUser): array
    {
        $roleIds = $this->rbac()->getAssignedRoles(self::RBAC_TYPE, $kdAtpmUser)->pluck('id')->all();

        if (empty($roleIds)) {
            return [];
        }

        return DB::table('ms_sales_atpm_menu_roles')->whereIn('role_id', $roleIds)
            ->pluck('menu_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /**
     * Menu yang dimiliki user (role + override grant - override revoke).
     * Return collection object dengan properti fk_sales_atpm_master_menu (kompatibel dengan view lama).
     */
    public function findUserMenuBykd($kd_atpm_user)
    {
        $kd = (string) $kd_atpm_user;
        $access = array_flip($this->roleMenuIds($kd));

        $overrides = DB::table('ms_sales_atpm_menu_users')->where('kd_atpm_user', $kd)->get(['menu_id', 'is_granted']);
        foreach ($overrides as $o) {
            if ($o->is_granted) {
                $access[(int) $o->menu_id] = true;
            } else {
                unset($access[(int) $o->menu_id]);
            }
        }

        return collect(array_keys($access))->map(fn ($id) => (object) [
            'fk_kd_atpm_user'           => $kd,
            'fk_sales_atpm_master_menu' => $id,
            'is_active'                 => 1,
        ])->values();
    }

    public function findAllMasterMenu()
    {
        return $this->menuQuery()
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->orderBy('parent_id', 'asc')
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    protected $columnDtMasterMenu = array('id', 'label', 'route_name', 'icon', 'parent_id', 'sort_order', 'is_active');
    public function findAllDatatableMasterMenu($srcText)
    {
        $query = $this->menuQuery();

        if (!empty($srcText)) {
            $query->where(function ($q) use ($srcText) {
                foreach ($this->columnDtMasterMenu as $item) {
                    $q->orWhere($item, 'like', "%{$srcText}%");
                }
            });
        }

        $query->orderBy('id');
        $query->orderBy('parent_id');

        return $query->get();
    }

    /**
     * Simpan menu user: menu yang dicentang = menu yang tampil di sidebar user.
     * Disimpan sebagai override terhadap menu role user (grant / revoke);
     * role default 'user' dipasang jika user belum punya role Sales.
     */
    public function updateUserMenuById($dto)
    {
        try {
            $kdAtpmUser = (string) $dto['kd_atpm_user'];
            $by         = (string) session('user.id');
            $rbac       = $this->rbac();

            $activeMenuIds = DB::table('ms_sales_atpm_menus')->where('is_active', true)
                ->pluck('id')->map(fn ($id) => (int) $id)->all();

            // Parent ikut dicentang supaya child tidak hilang dari sidebar
            $checked = array_intersect(
                $rbac->withMenuAncestors(self::RBAC_TYPE, array_map('intval', $dto['arrMenuIds'] ?? [])),
                $activeMenuIds
            );

            DB::transaction(function () use ($rbac, $kdAtpmUser, $by, $activeMenuIds, $checked) {
                if ($rbac->getAssignedRoles(self::RBAC_TYPE, $kdAtpmUser)->isEmpty()) {
                    $defaultRoleId = (int) DB::table('ms_sales_atpm_roles')->where('slug', 'user')->value('id');
                    if ($defaultRoleId) {
                        DB::table('ms_sales_atpm_user_roles')->insertOrIgnore([
                            'kd_atpm_user' => $kdAtpmUser, 'role_id' => $defaultRoleId,
                            'assigned_by' => $by, 'assigned_at' => now(),
                        ]);
                    }
                }

                $fromRole   = array_flip($this->roleMenuIds($kdAtpmUser));
                $checkedSet = array_flip($checked);
                $map = [];

                foreach ($activeMenuIds as $menuId) {
                    $isChecked = isset($checkedSet[$menuId]);
                    $inRole    = isset($fromRole[$menuId]);

                    $map[$menuId] = $isChecked === $inRole ? 'default' : ($isChecked ? 'grant' : 'revoke');
                }

                $rbac->syncUserMenuOverrides(self::RBAC_TYPE, $kdAtpmUser, $map, $by);
            });

            $rbac->flushCache(self::RBAC_TYPE);

            return ['status' => true, 'message' => 'Save Data Success', 'data' => null];
        } catch (\Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }


    public function findUserPermissionBykd($kd_atpm_user)
    {
        $query = DB::table('tr_sales_atpm_user_permission')
            ->where('fk_kd_atpm_user', $kd_atpm_user)
            ->where('is_active', 1)
            ->get();

        return $query;
    }


    public function findAllMasterPermission()
    {
        $query = DB::table('ms_sales_atpm_master_permission')
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->orderBy('group', 'asc')
            ->orderBy('permission', 'asc')
            ->get();

        return $query;
    }

    public function updateUserPermissionById($dto)
    {
        DB::beginTransaction();

        try {
            $kdAtpmUser    = $dto['kd_atpm_user'];
            $permissionIds = $dto['arrPermissionIds'] ?? [];
            $userId        = session('user.id');
            $now           = now();

            // Validate IDs against master
            $validIds = DB::table('ms_sales_atpm_master_permission')
                ->whereIn('id', $permissionIds)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            // Deactivate all current user permissions
            DB::table('tr_sales_atpm_user_permission')
                ->where('fk_kd_atpm_user', $kdAtpmUser)
                ->update([
                    'is_active'    => 0,
                    'updated_by'   => $userId,
                    'date_updated' => $now,
                ]);

            // Re-activate existing rows
            $existing = DB::table('tr_sales_atpm_user_permission')
                ->where('fk_kd_atpm_user', $kdAtpmUser)
                ->whereIn('fk_sales_atpm_master_permission', $validIds)
                ->pluck('fk_sales_atpm_master_permission')
                ->toArray();

            if (!empty($existing)) {
                DB::table('tr_sales_atpm_user_permission')
                    ->where('fk_kd_atpm_user', $kdAtpmUser)
                    ->whereIn('fk_sales_atpm_master_permission', $existing)
                    ->update([
                        'is_active'    => 1,
                        'updated_by'   => $userId,
                        'date_updated' => $now,
                    ]);
            }

            // Insert new rows
            $newIds = array_diff($validIds, $existing);
            if (!empty($newIds)) {
                $insertData = [];
                foreach ($newIds as $permId) {
                    $insertData[] = [
                        'fk_kd_atpm_user'               => $kdAtpmUser,
                        'fk_sales_atpm_master_permission' => $permId,
                        'is_active'                      => 1,
                        'created_by'                     => $userId,
                        'date_created'                   => $now,
                    ];
                }
                DB::table('tr_sales_atpm_user_permission')->insert($insertData);
            }

            DB::commit();

            return ['status' => true, 'message' => 'Save Data Success', 'data' => null];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['status' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }




    // protected $columnDt = array('id', 'title', 'route', 'icon', 'parent_id', 'order', 'is_active');
    // public function findAll($srcText)
    // {

    //     $query = DB::table('ms_sales_atpm_master_menu')
    //                 ->select(
    //                     'id', 'title', 'route', 'icon', 'parent_id', 'order', 'is_active'
    //                 );

    //     if (!empty($srcText)) {
    //         $query->where(function ($q) use ($srcText) {
    //             foreach ($this->columnDt as $item) {
    //                 $q->orWhere($item, 'like', "%{$srcText}%");
    //             }
    //         });
    //     }

    //     $query->orderBy('id');
    //     $query->orderBy('parent_id');

    //     return $query->get();

    //     // return $query->paginate(100);
    // }


}
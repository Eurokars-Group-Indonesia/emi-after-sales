<?php

namespace App\Repositories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
    public function findUserMenuBykd($kd_atpm_user)
    {
        $query = DB::table('tr_sales_atpm_user_menu')
            ->where('fk_kd_atpm_user', $kd_atpm_user)
            ->where('is_active', 1)
            ->get();

        return $query;
    }

    public function findAllMasterMenu()
    {
        $query = DB::table('ms_sales_atpm_master_menu')
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->orderBy('parent_id', 'asc')
            ->orderBy('order', 'asc')
            ->get();

        return $query;
    }

    protected $columnDtMasterMenu = array('id', 'title', 'route', 'icon', 'parent_id', 'order', 'is_active');
    public function findAllDatatableMasterMenu($srcText)
    {

        $query = DB::table('ms_sales_atpm_master_menu')
            ->select(
                'id',
                'title',
                'route',
                'icon',
                'parent_id',
                'order',
                'is_active'
            );

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

        // return $query->paginate(100);
    }


    public function updateUserMenuById($dto)
    {
        DB::beginTransaction(); 

        try {   

            $kdAtpmUser = $dto['kd_atpm_user'];
            $menuIds = $dto['arrMenuIds'] ?? [];
            $userId = session('user.id');
            $now = now();

            // Validasi ID menu yang memang ada di master
            $validMenuIds = DB::table('ms_sales_atpm_master_menu')
                ->whereIn('id', $menuIds)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            // Nonaktifkan semua menu user
            DB::table('tr_sales_atpm_user_menu')
                ->where('fk_kd_atpm_user', $kdAtpmUser)
                ->update([
                    'is_active' => 0,
                    'updated_by' => $userId,
                    'date_updated' => $now,
                ]);

            // Ambil menu yang sudah pernah dibuat
            $existingMenus = DB::table('tr_sales_atpm_user_menu')
                ->where('fk_kd_atpm_user', $kdAtpmUser)
                ->whereIn('fk_sales_atpm_master_menu', $validMenuIds)
                ->pluck('fk_sales_atpm_master_menu')
                ->toArray();


            // Yang sudah ada → aktifkan
            if (!empty($existingMenus)) {
                DB::table('tr_sales_atpm_user_menu')
                    ->where('fk_kd_atpm_user', $kdAtpmUser)
                    ->whereIn('fk_sales_atpm_master_menu', $existingMenus)
                    ->update([
                        'is_active' => 1,
                        'updated_by' => $userId,
                        'date_updated' => $now,
                    ]);
            }

            // Yang belum ada → insert
            $newMenuIds = array_diff($validMenuIds, $existingMenus);

            if (!empty($newMenuIds)) {

                $insertData = [];

                foreach ($newMenuIds as $menuId) {
                    $insertData[] = [
                        'fk_kd_atpm_user' => $kdAtpmUser,
                        'fk_sales_atpm_master_menu' => $menuId,
                        'is_active' => 1,
                        'created_by' => $userId,
                        'date_created' => $now,
                    ];
                }

                DB::table('tr_sales_atpm_user_menu')
                    ->insert($insertData);
            }

            DB::commit();

            $callback = [
                'status' => true,
                'message' => 'Save Data Success',
                'data' => null
            ];

            


        } catch (Exception $e) {
            DB::rollBack(); 

             $callback = [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }

        return $callback;
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
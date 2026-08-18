<?php

namespace App\Repositories;
use Illuminate\Support\Facades\DB;

interface SalesAtpmUserMenuInterface
{
    // public function findUserMenuBykd($kd_atpm_user);    // return first();
}

class SalesAtpmUserMenuRepository implements SalesAtpmUserMenuInterface
{
//    public function findUserMenuBykd($kd_atpm_user)
//    {
//         $query = DB::table('tr_sales_atpm_user_menu')
//         ->where('fk_kd_atpm_user', $kd_atpm_user)
//         ->where('is_active', 1)
//         ->get();

//         return $query;
//    }




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
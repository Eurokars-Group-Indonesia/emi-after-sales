<?php

namespace App\Repositories;
use Illuminate\Support\Facades\DB;

interface SalesAtpmMasterPermissionInterface
{
    public function findAll($srcText);
}
// ms_permission_sales_atpm
class SalesAtpmMasterPermissionRepository implements SalesAtpmMasterPermissionInterface
{
    protected $columnDt = array('permission', 'description', 'group', 'is_active');
    public function findAll($srcText)
    {

        $query = DB::table('ms_sales_atpm_master_permission')
                    ->select(
                        'id', 'permission', 'description', 'group', 'is_active'
                    );

        if (!empty($srcText)) {
            $query->where(function ($q) use ($srcText) {
                foreach ($this->columnDt as $item) {
                    $q->orWhere($item, 'like', "%{$srcText}%");
                }
            });
        }

        $query->orderBy('group');

        return $query->get();

        // return $query->paginate(100);
    }

    
}
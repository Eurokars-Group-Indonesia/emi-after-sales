<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesAtpmMasterPermission extends Model
{
    protected $table = 'ms_sales_atpm_master_permission';
    public $timestamps = false; 
    
    // protected $fillable = [
    //     'title',
    //     'route',
    //     'icon',
    //     'parent_id',
    //     'order',
    //     'is_active'
    // ];

    // public function children()
    // {
    //     return $this->hasMany(SalesAtpmMasterPermission::class, 'parent_id')
    //         ->orderBy('order')
    //         ->with('children');
    // }

    // public function parent()
    // {
    //     return $this->belongsTo(SalesAtpmMasterPermission::class, 'parent_id');
    // }
}

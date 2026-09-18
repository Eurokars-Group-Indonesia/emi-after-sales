<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
class SalesAtpmMasterMenu extends Model
{
    protected $table = 'ms_sales_atpm_master_menu';
    public $timestamps = false;
    
    protected $fillable = [
        'title',
        'route',
        'icon',
        'parent_id',
        'order',
        'is_active'
    ];

    // public function children()
    // {
    //     return $this->hasMany(SalesAtpmMasterMenu::class, 'parent_id')
    //         ->where('is_active', true)
    //         ->orderBy('order')
    //         ->with('children');
    // }

    public function children()
    {
        return $this->hasMany(SalesAtpmMasterMenu::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('order');
    }

    public function parent()
    {
        return $this->belongsTo(SalesAtpmMasterMenu::class, 'parent_id');
    }
}
    */

class SalesAtpmMasterMenu extends Model
{
    protected $table = 'ms_sales_atpm_master_menu';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'route',
        'icon',
        'parent_id',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function children()
    {
        return $this->hasMany(
            SalesAtpmMasterMenu::class,
            'parent_id',
            'id'
        )->where('is_active', true)->orderBy('order', 'asc');
    }

    
    // public function children()
    // {
    //     return $this->hasMany(SalesAtpmMasterMenu::class, 'parent_id')
    //         ->where('is_active', true)
    //         ->orderBy('order');
    // }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }


    public function parent()
    {
        return $this->belongsTo(SalesAtpmMasterMenu::class, 'parent_id');
    }
}

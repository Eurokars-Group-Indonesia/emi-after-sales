<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuAtpmAfterSales extends Model
{
    protected $table = 'ms_menu_aftersales_atpm';
    public $timestamps = false;
    
    protected $fillable = [
        'title',
        'route',
        'icon',
        'parent_id',
        'order',
        'is_active'
    ];

    public function children()
    {
        return $this->hasMany(MenuAtpmAfterSales::class, 'parent_id')
            ->orderBy('order')
            ->with('children');
    }

    public function parent()
    {
        return $this->belongsTo(MenuAtpmAfterSales::class, 'parent_id');
    }
}

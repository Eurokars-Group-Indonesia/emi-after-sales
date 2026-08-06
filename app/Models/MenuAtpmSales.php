<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuAtpmSales extends Model
{
    protected $table = 'menu_sales_atpm';
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
        return $this->hasMany(MenuAtpmSales::class, 'parent_id')
            ->orderBy('order')
            ->with('children');
    }

    public function parent()
    {
        return $this->belongsTo(MenuAtpmSales::class, 'parent_id');
    }
}

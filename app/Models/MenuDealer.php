<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuDealer extends Model
{
    protected $table = 'menu_dealer';
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
        return $this->hasMany(MenuDealer::class, 'parent_id')
            ->orderBy('order')
            ->with('children');
    }

    public function parent()
    {
        return $this->belongsTo(MenuDealer::class, 'parent_id');
    }
}

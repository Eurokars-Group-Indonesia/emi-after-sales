<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuAtpm extends Model
{
    protected $table = 'menu_atpm';
    
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
        return $this->hasMany(MenuAtpm::class, 'parent_id')
            ->orderBy('order')
            ->with('children');
    }

    public function parent()
    {
        return $this->belongsTo(MenuAtpm::class, 'parent_id');
    }
}

<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;

class AtpmMenu extends Model
{
    protected $table = 'ms_afsel_atpm_menus';

    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_updated';

    protected $fillable = [
        'parent_id', 'name', 'label', 'route_name', 'url', 'icon', 'sort_order',
        'permission_slug', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function roles()
    {
        return $this->belongsToMany(AtpmRole::class, 'ms_afsel_atpm_menu_roles', 'menu_id', 'role_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** URL tujuan menu: route_name > url > '#' */
    public function href(): string
    {
        if ($this->route_name && \Illuminate\Support\Facades\Route::has($this->route_name)) {
            return route($this->route_name);
        }

        return $this->url ?: '#';
    }
}

<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;

class AtpmRole extends Model
{
    protected $table = 'ms_afsel_atpm_roles';

    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_updated';

    protected $fillable = ['name', 'slug', 'description', 'level', 'is_system', 'is_active', 'created_by', 'updated_by'];

    protected $casts = ['is_system' => 'boolean', 'is_active' => 'boolean', 'level' => 'integer'];

    public function permissions()
    {
        return $this->belongsToMany(AtpmPermission::class, 'ms_afsel_atpm_role_permissions', 'role_id', 'permission_id');
    }

    public function menus()
    {
        return $this->belongsToMany(AtpmMenu::class, 'ms_afsel_atpm_menu_roles', 'role_id', 'menu_id');
    }

    public function users()
    {
        return $this->belongsToMany(AtpmUser::class, 'ms_afsel_atpm_user_roles', 'role_id', 'kd_atpm_user')
            ->withPivot('assigned_by', 'assigned_at', 'expires_at');
    }
}

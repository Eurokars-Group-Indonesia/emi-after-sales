<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;

class DealerRole extends Model
{
    protected $table = 'ms_afsel_dealer_roles';

    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_updated';

    protected $fillable = ['name', 'slug', 'description', 'level', 'is_system', 'is_active', 'created_by', 'updated_by'];

    protected $casts = ['is_system' => 'boolean', 'is_active' => 'boolean', 'level' => 'integer'];

    public function permissions()
    {
        return $this->belongsToMany(DealerPermission::class, 'ms_afsel_dealer_role_permissions', 'role_id', 'permission_id');
    }

    public function menus()
    {
        return $this->belongsToMany(DealerMenu::class, 'ms_afsel_dealer_menu_roles', 'role_id', 'menu_id');
    }

    public function users()
    {
        return $this->belongsToMany(DealerUser::class, 'ms_afsel_dealer_user_roles', 'role_id', 'kd_dealer_user')
            ->withPivot('assigned_by', 'assigned_at', 'expires_at');
    }
}

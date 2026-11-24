<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;

class DealerPermission extends Model
{
    protected $table = 'ms_afsel_dealer_permissions';

    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_updated';

    protected $fillable = ['name', 'slug', 'group_name', 'description', 'created_by', 'updated_by'];

    public function roles()
    {
        return $this->belongsToMany(DealerRole::class, 'ms_afsel_dealer_role_permissions', 'permission_id', 'role_id');
    }
}

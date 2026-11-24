<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;

class AtpmPermission extends Model
{
    protected $table = 'ms_afsel_atpm_permissions';

    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_updated';

    protected $fillable = ['name', 'slug', 'group_name', 'description', 'created_by', 'updated_by'];

    public function roles()
    {
        return $this->belongsToMany(AtpmRole::class, 'ms_afsel_atpm_role_permissions', 'permission_id', 'role_id');
    }
}

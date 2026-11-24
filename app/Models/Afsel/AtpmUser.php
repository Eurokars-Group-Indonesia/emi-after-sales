<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;

class AtpmUser extends Model
{
    protected $table = 'ms_afsel_atpm_user';
    protected $primaryKey = 'kd_atpm_user';
    protected $keyType = 'string';
    public $incrementing = false;

    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_updated';

    protected $fillable = [
        'kd_atpm_user', 'nm_atpm_user', 'username', 'password', 'email', 'picture', 'is_active', 'picture_ext', 'tgl_masuk',
        'fk_atpm_level', 'fk_atpm_department',
        'failed_login_attempts', 'locked_until', 'last_login_at', 'last_login_ip', 'password_changed_at',
        'created_by', 'updated_by',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active'     => 'boolean',
        'tgl_masuk'     => 'datetime',
        'locked_until'  => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(AtpmRole::class, 'ms_afsel_atpm_user_roles', 'kd_atpm_user', 'role_id')
            ->withPivot('assigned_by', 'assigned_at', 'expires_at');
    }

    public function menuOverrides()
    {
        return $this->hasMany(AtpmMenuUser::class, 'kd_atpm_user', 'kd_atpm_user');
    }
}

<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;

class DealerUser extends Model
{
    protected $table = 'ms_afsel_dealer_user';
    protected $primaryKey = 'kd_dealer_user';
    protected $keyType = 'string';
    public $incrementing = false;

    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_updated';

    protected $fillable = [
        'kd_dealer_user', 'nm_dealer_user', 'username', 'password', 'email', 'picture', 'is_active', 'picture_ext', 'tgl_masuk',
        'fk_dealer', 'fk_dealer_level',
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
        return $this->belongsToMany(DealerRole::class, 'ms_afsel_dealer_user_roles', 'kd_dealer_user', 'role_id')
            ->withPivot('assigned_by', 'assigned_at', 'expires_at');
    }

    public function menuOverrides()
    {
        return $this->hasMany(DealerMenuUser::class, 'kd_dealer_user', 'kd_dealer_user');
    }
}

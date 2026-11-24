<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;

class DealerMenuUser extends Model
{
    protected $table = 'ms_afsel_dealer_menu_users';

    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_updated';

    protected $fillable = ['menu_id', 'kd_dealer_user', 'is_granted', 'created_by', 'updated_by'];

    protected $casts = ['is_granted' => 'boolean'];

    public function menu()
    {
        return $this->belongsTo(DealerMenu::class, 'menu_id');
    }
}

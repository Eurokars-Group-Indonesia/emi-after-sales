<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SalesAtpmMasterMenu;

class SalesAtpmMenuUser extends Model
{
    protected $table = 'tr_sales_atpm_user_menu';
    public $timestamps = false;
    
    protected $fillable = [
        'fk_sales_atpm_user',
        'fk_sales_atpm_master_menu',
        'order',
        'is_active'
    ];

    public function masterMenu()
    {
        return $this->belongsTo(SalesAtpmMasterMenu::class, 'fk_sales_atpm_master_menu', 'id');
    }
}

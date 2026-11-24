<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

/** Role RBAC Sales ATPM (ms_sales_atpm_roles). */
class SalesAtpmRbacRole extends Model
{
    protected $table = 'ms_sales_atpm_roles';

    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_updated';

    protected $fillable = ['name', 'slug', 'description', 'level', 'is_system', 'is_active', 'created_by', 'updated_by'];

    protected $casts = ['is_system' => 'boolean', 'is_active' => 'boolean', 'level' => 'integer'];

    public function menus()
    {
        return $this->belongsToMany(SalesAtpmRbacMenu::class, 'ms_sales_atpm_menu_roles', 'role_id', 'menu_id');
    }
}

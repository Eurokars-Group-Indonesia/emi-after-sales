<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;

/**
 * Category Service Vehicle 2 (tblcategory_2_vehicles), turunan dari Category Service Vehicle 1
 * lewat fk_category_vehicles. Kode kd_category_2_vehicles dibuat lewat Document Numbering.
 */
class CategoryVehicle2 extends Model
{
    protected $table = 'tblcategory_2_vehicles';
    protected $primaryKey = 'kd_category_2_vehicles';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['kd_category_2_vehicles', 'nm_category_2_vehicles', 'fk_category_vehicles', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function category()
    {
        return $this->belongsTo(CategoryVehicle::class, 'fk_category_vehicles', 'kd_category_vehicles');
    }
}

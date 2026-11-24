<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;

/**
 * Category Service Vehicle 1 (tblcategory_vehicles).
 * Kode kd_category_vehicles dibuat lewat Document Numbering (yyyymmdd + 20 digit).
 */
class CategoryVehicle extends Model
{
    protected $table = 'tblcategory_vehicles';
    protected $primaryKey = 'kd_category_vehicles';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public const JENIS = ['Customer Service Sales', 'Internal Service Sales'];

    protected $fillable = ['kd_category_vehicles', 'nm_category_vehicles', 'is_active', 'jenis'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function children()
    {
        return $this->hasMany(CategoryVehicle2::class, 'fk_category_vehicles', 'kd_category_vehicles');
    }
}

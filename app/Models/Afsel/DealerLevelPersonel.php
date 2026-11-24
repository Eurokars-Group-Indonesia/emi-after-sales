<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Master Level Personel After Sales (afsel_dealer_level_personel). */
class DealerLevelPersonel extends Model
{
    protected $table = 'afsel_dealer_level_personel';
    protected $primaryKey = 'dealer_level_personel_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public const JENIS = ['Direct', 'Indirect'];

    protected $fillable = ['dealer_level_personel_id', 'dealer_level_personel', 'is_active', 'jenis'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /**
     * ID baru dengan format sama seperti nextserial WRS: YYYYMMDD + 20 digit nomor urut per hari.
     * Contoh: 2026110600000000000000000001. Dipanggil di dalam transaksi (lockForUpdate).
     */
    public static function nextId(): string
    {
        $prefix = now()->format('Ymd');

        $last = DB::table((new static)->getTable())
            ->where('dealer_level_personel_id', 'like', $prefix . '%')
            ->whereRaw('CHAR_LENGTH(dealer_level_personel_id) = 28')
            ->lockForUpdate()
            ->max('dealer_level_personel_id');

        $seq = $last ? ((int) substr($last, 8)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 20, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Models\Afsel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Master Dealer Personel After Sales (afsel_dealer_personel). */
class DealerPersonel extends Model
{
    protected $table = 'afsel_dealer_personel';
    protected $primaryKey = 'dealer_personel_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'dealer_personel_id', 'name', 'nm_panggilan', 'dealer_level_personel_id', 'nik', 'alamat',
        'picture', 'picture_ext', 'tgl_keluar', 'alasan_keluar', 'is_blacklist', 'is_blacklist_atpm',
        'is_active', 'fk_dealer', 'tgl_masuk', 'no_ktp',
    ];

    protected $casts = [
        'is_blacklist'      => 'boolean',
        'is_blacklist_atpm' => 'boolean',
        'is_active'         => 'boolean',
        'tgl_keluar'        => 'datetime',
        'tgl_masuk'         => 'datetime',
    ];

    public function level()
    {
        return $this->belongsTo(DealerLevelPersonel::class, 'dealer_level_personel_id', 'dealer_level_personel_id');
    }

    /**
     * ID baru format WRS: yyyymmdd + 20 digit urutan (28 karakter), mis. 2026111000000000000000000001.
     * Panggil di dalam transaksi (lockForUpdate) agar tidak bentrok saat input bersamaan.
     */
    public static function nextId(): string
    {
        $prefix = now()->format('Ymd');

        $last = DB::table((new static)->getTable())
            ->where('dealer_personel_id', 'like', $prefix . '%')
            ->whereRaw('CHAR_LENGTH(dealer_personel_id) = 28')
            ->lockForUpdate()
            ->max('dealer_personel_id');

        $seq = $last ? ((int) substr($last, 8)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 20, '0', STR_PAD_LEFT);
    }

    /**
     * Pesan error bila Nama atau No KTP sudah dipakai personel lain yang sedang Aktif (semua dealer), null bila aman.
     * Nama dibandingkan tanpa beda huruf besar/kecil & spasi di awal/akhir.
     *
     * @param string|null $ownDealer dealer pemanggil, untuk pesan "di dealer ini" / "di dealer lain"
     */
    public static function activeDuplicate(?string $name, ?string $ktp, ?string $exceptId = null, ?string $ownDealer = null): ?string
    {
        $active = fn () => DB::table((new static)->getTable())->where('is_active', true)
            ->when($exceptId, fn ($q) => $q->where('dealer_personel_id', '!=', $exceptId));

        $where = fn ($dup) => $ownDealer === null
            ? 'di dealer ' . $dup->fk_dealer
            : ($dup->fk_dealer === $ownDealer ? 'di dealer ini' : 'di dealer lain');

        $name = trim((string) $name);
        if ($name !== '' && ($dup = $active()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first(['fk_dealer']))) {
            return "Nama \"{$name}\" sudah terdaftar sebagai personel aktif {$where($dup)}.";
        }

        $ktp = trim((string) $ktp);
        if ($ktp !== '' && ($dup = $active()->where('no_ktp', $ktp)->first(['fk_dealer']))) {
            return "No KTP {$ktp} sudah terdaftar sebagai personel aktif {$where($dup)}.";
        }

        return null;
    }
}

<?php

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Semua halaman After Sales dealer pindah ke format domain.com/aftersales/dealer/...:
 *  - /dealer/aftersales/home        → /aftersales/dealer/home  (/aftersales/dealer redirect ke sini)
 *    (route dealer.aftersales.home → aftersales.dealer.home)
 *  - /dealer/customer/customer-data → /aftersales/dealer/customer/customer-data
 *    (route dealer.customer.customer-data → aftersales.dealer.customer.customer-data)
 *  - /dealer/service/service-data   → /aftersales/dealer/service/service-data
 *    (route dealer.service.service-data → aftersales.dealer.service.service-data)
 *
 * Nama/URL sementara dealer.aftersales.* & /dealer/aftersales/... (versi awal migration ini) ikut dipetakan,
 * supaya database yang sudah sempat menjalankan versi lama juga terkoreksi.
 *
 * Kolom route_name dipakai untuk render sidebar & cek akses route, jadi data menu harus ikut diubah.
 */
return new class extends Migration
{
    private const BY = 'SYSTEM-MIGRATION';

    /** baru => [nama lama, ...] */
    private const ROUTES = [
        'aftersales.dealer.home'                   => ['dealer.aftersales.home'],
        'aftersales.dealer.customer.customer-data' => ['dealer.customer.customer-data', 'dealer.aftersales.customer.customer-data'],
        'aftersales.dealer.service.service-data'   => ['dealer.service.service-data', 'dealer.aftersales.service.service-data'],
    ];

    /** baru => [url lama, ...] */
    private const URLS = [
        '/aftersales/dealer/home'                   => ['/dealer/aftersales/home'],
        '/aftersales/dealer/customer/customer-data' => ['/dealer/customer/customer-data', '/dealer/aftersales/customer/customer-data'],
        '/aftersales/dealer/service/service-data'   => ['/dealer/service/service-data', '/dealer/aftersales/service/service-data'],
    ];

    public function up(): void
    {
        $this->apply('route_name', self::ROUTES);
        $this->apply('url', self::URLS);

        app(AfselRbacService::class)->flushCache('dealer');
    }

    public function down(): void
    {
        // Kembali ke nama/URL asli sebelum migration ini (elemen pertama tiap daftar)
        $this->apply('route_name', $this->reverse(self::ROUTES));
        $this->apply('url', $this->reverse(self::URLS));

        app(AfselRbacService::class)->flushCache('dealer');
    }

    /** @param array<string, string[]> $map baru => [lama, ...] */
    private function apply(string $column, array $map): void
    {
        $now = now();

        foreach ($map as $to => $froms) {
            DB::table('ms_afsel_dealer_menus')->whereIn($column, $froms)
                ->update([$column => $to, 'updated_by' => self::BY, 'date_updated' => $now]);
        }
    }

    private function reverse(array $map): array
    {
        $reversed = [];
        foreach ($map as $new => $olds) {
            $reversed[$olds[0]] = [$new];
        }

        return $reversed;
    }
};

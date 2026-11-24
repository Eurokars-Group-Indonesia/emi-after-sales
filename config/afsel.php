<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver login WRS Connect - After Sales
    |--------------------------------------------------------------------------
    | local : login ke tabel ms_afsel_atpm_user / ms_afsel_dealer_user + RBAC lokal
    | api   : login lama via API_SERVICE (eurokars-wrs-login-service), tanpa RBAC
    */
    'auth_driver' => env('AFSEL_AUTH_DRIVER', 'local'),

    // Jumlah gagal login berturut-turut sebelum akun dikunci sementara
    'max_login_attempts' => (int) env('AFSEL_MAX_LOGIN_ATTEMPTS', 5),

    // Lama akun dikunci (menit)
    'lockout_minutes' => (int) env('AFSEL_LOCKOUT_MINUTES', 15),

    // TTL cache menu & permission (detik)
    'cache_ttl' => (int) env('AFSEL_RBAC_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Foto Dealer Personel
    |--------------------------------------------------------------------------
    | Kolom picture di WRS hanya berisi nama file; file fotonya ada di server WRS lama.
    | Isi dengan template URL (http/https) ATAU path folder lokal/network share. Placeholder:
    |   {id}      : dealer_personel_id            contoh 2012100200000000000000000014
    |   {picture} : nama file di kolom picture    contoh Sunset.jpg (di-encode untuk URL)
    |   {ext}     : picture_ext                   contoh .jpg
    | Path relatif dihitung dari root project. Contoh:
    |   AFSEL_PERSONEL_PHOTO_SOURCE="storage/app/private/afsel/dealer_personil_aftersales/{id}{ext}"   (nama file = kode personel + ext)
    | Simpan foto di luar folder public/ agar hanya bisa dibuka lewat aplikasi (dicek permission).
    |   AFSEL_PERSONEL_PHOTO_SOURCE="http://wrs-server/upload/personil/{id}{ext}"
    |   AFSEL_PERSONEL_PHOTO_SOURCE="\\fileserver\wrs\upload\personil\{picture}"
    | Kosong = foto tidak ditampilkan (modal memakai avatar inisial).
    */
    'personel_photo_source' => env('AFSEL_PERSONEL_PHOTO_SOURCE'),

    // Tautan "Contact Support" di halaman login After Sales (mailto:, https://, atau kosong = tanpa tautan)
    'support_url' => env('AFSEL_SUPPORT_URL'),

    /*
    |--------------------------------------------------------------------------
    | Upload Service (Dealer)
    |--------------------------------------------------------------------------
    | wrs_connection : koneksi DB tempat tblfaktur (status_bast) & tblinventory WRS Sales berada
    | max_rows       : jumlah baris data maksimal per file upload
    | max_file_kb    : ukuran file maksimal (KB)
    */
    'service_upload' => [
        'wrs_connection' => env('AFSEL_SERVICE_UPLOAD_WRS_CONNECTION', 'db_wrs_sales'),
        'max_rows'       => (int) env('AFSEL_SERVICE_UPLOAD_MAX_ROWS', 2000),
        'max_file_kb'    => (int) env('AFSEL_SERVICE_UPLOAD_MAX_FILE_KB', 10240),
    ],

];

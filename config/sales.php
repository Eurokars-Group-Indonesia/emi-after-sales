<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver login WRS Connect - Sales (/sales)
    |--------------------------------------------------------------------------
    | db  : login langsung ke database WRS Sales (tblatpm_user / tbldealer_user) + RBAC Sales lokal
    | api : login lama via API_SERVICE (eurokars-wrs-login-service), tanpa RBAC
    */
    'auth_driver' => env('SALES_AUTH_DRIVER', 'db'),

    // Nama koneksi database WRS Sales (config/database.php)
    'auth_connection' => env('SALES_AUTH_CONNECTION', 'db_wrs_sales'),

    // Batas percobaan login gagal per username + IP dalam 1 menit
    'max_login_attempts' => (int) env('SALES_MAX_LOGIN_ATTEMPTS', 10),

    /*
    |--------------------------------------------------------------------------
    | RBAC Sales (tabel ms_sales_atpm_* / ms_sales_dealer_*)
    |--------------------------------------------------------------------------
    | rbac_cache_ttl     : TTL cache menu & permission (detik)
    | user_check_ttl     : interval cek ulang status aktif user ke database WRS Sales (detik)
    | default_role.{tipe}: slug role yang otomatis diberikan saat user login pertama kali dan belum punya role.
    |                      Kosong = user tanpa role ditolak saat login (role diatur lewat Access Control).
    */
    'rbac_cache_ttl' => (int) env('SALES_RBAC_CACHE_TTL', 3600),

    'user_check_ttl' => (int) env('SALES_USER_CHECK_TTL', 60),

    'default_role' => [
        'atpm'   => env('SALES_DEFAULT_ROLE_ATPM'),
        'dealer' => env('SALES_DEFAULT_ROLE_DEALER', 'dealer-user'),
    ],

];

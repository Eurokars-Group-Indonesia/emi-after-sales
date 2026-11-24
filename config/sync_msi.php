<?php

/*
|--------------------------------------------------------------------------
| Sync WRS Connect After Sales → MSI (Customer CSI)
|--------------------------------------------------------------------------
| Menggantikan script lama upload_customer_csi.php (PHP 5 + COM ADODB, hanya jalan di Windows).
|
| Alur (sama dengan script lama):
|  1. Ambil customer yang berubah (tblcustomer_log.log_action_date di rentang tanggal) + KPI service terakhir,
|     SA dari work planning, agama, model dari inventory (use_by = Faktur), tanggal handover dari faktur.
|     Lalu disaring dengan tblsetting.filter_csi.
|  2. Per baris: dealer (dbo.dealers.dealer_code) & user (dbo.users.kd_user_wrs) harus terdaftar di MSI.
|     Belum ada di dbo.members (vin + dealer + handover_date) → INSERT, sudah ada → UPDATE.
|  3. TIDAK ADA DELETE di MSI.
|  4. Jika SEMUA baris sukses: tblkpi.uploaded_to_msi = 1 & tblsetting.last_update_csi = hari ini.
|     Ada satu baris saja yang dilewati/gagal → keduanya tidak diubah (perilaku lama), rentang yang sama dikirim ulang
|     di jadwal berikutnya.
*/

return [

    'label'      => 'Sync MSI (Customer CSI)',
    'legacy'     => 'upload_customer_csi.php',

    // Nama baris di ms_sync_db_connection (menu Koneksi Database Sync)
    'connection' => env('SYNC_MSI_CONNECTION', 'MSI'),

    'timezone'   => env('SYNC_MSI_TIMEZONE', 'Asia/Jakarta'),

    // Setiap hari jam 21:00 WIB
    'cron'       => env('SYNC_MSI_CRON', '0 21 * * *'),
    'schedule'   => 'Setiap hari jam 21:00 WIB',

    // Batas waktu lock (detik). Jika proses mati, lock otomatis lepas setelah waktu ini.
    'lock_seconds'  => (int) env('SYNC_MSI_LOCK_SECONDS', 4 * 3600),

    // Proses RUNNING tanpa progres (heartbeat) lebih dari ini dianggap mati → lock dilepas otomatis
    'stale_minutes' => (int) env('SYNC_MSI_STALE_MINUTES', 30),

    // Simpan juga baris yang sukses (INSERT/UPDATE) ke tr_sync_msi_log_detail.
    // Default false: hanya baris SKIPPED / FAILED yang disimpan supaya tabel log tidak cepat membesar.
    'log_success_rows' => (bool) env('SYNC_MSI_LOG_SUCCESS_ROWS', false),

    // Rentang manual maksimal (hari) dari menu monitoring
    'max_manual_days' => (int) env('SYNC_MSI_MAX_MANUAL_DAYS', 366),

    // Opsi koneksi SQL Server (driver pdo_sqlsrv / ODBC Driver 18)
    'sqlsrv' => [
        // ODBC Driver 18 default Encrypt=yes; server MSI lama biasanya belum pakai TLS → default 'no'
        'encrypt'                  => env('SYNC_MSI_SQLSRV_ENCRYPT', 'no'),
        'trust_server_certificate' => (bool) env('SYNC_MSI_SQLSRV_TRUST_CERT', true),
        'login_timeout'            => (int) env('SYNC_MSI_SQLSRV_LOGIN_TIMEOUT', 15),
    ],

    // Level personel Service Advisor (script lama: tbldealer_personil_aftersales.fk_level_personil_aftersales)
    'sa_level_id' => '2012070600000000000000000003',

    /*
    | Kategori service (tblkpi.fk_category_1_vehicles + fk_category_2_vehicles) → reason_for_service & next_service_date.
    | cat2 = '' berarti kategori 2 kosong. days = null → next_service_date kosong.
    */
    'service_map' => [
        ['cat1' => '2012061900000000000000000007', 'cat2' => '',                             'code' => 'PDI',         'days' => 30],
        ['cat1' => '2012061900000000000000000003', 'cat2' => '2012061900000000000000000010', 'code' => 'WARRANTY',    'days' => null],
        ['cat1' => '2012061900000000000000000001', 'cat2' => '2012061900000000000000000002', 'code' => 'FS1',         'days' => 150],
        ['cat1' => '2012061900000000000000000001', 'cat2' => '2012061900000000000000000003', 'code' => 'FS2',         'days' => 180],
        ['cat1' => '2012061900000000000000000001', 'cat2' => '2012061900000000000000000004', 'code' => 'MAINTENANCE', 'days' => 180],
        ['cat1' => '2012061900000000000000000001', 'cat2' => '2012061900000000000000000001', 'code' => 'OTHER',       'days' => 180],
    ],
];

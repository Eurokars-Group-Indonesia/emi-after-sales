<?php

/*
|--------------------------------------------------------------------------
| Sync WRS Sales → WRS Connect After Sales
|--------------------------------------------------------------------------
| Menggantikan script lama sync_color.php, sync_model.php, sync_model_color.php, sync_type.php,
| sync_spk.php, sync_inventory.php dan sync_faktur.php.
|
| Aturan utama:
|  - Hanya INSERT data baru dan UPDATE data yang berubah (upsert). TIDAK PERNAH menghapus data di target.
|  - Sumber dibaca per blok (keyset pagination pada kolom key), target ditulis per blok dalam transaksi.
|  - Baris dengan status 'Canceled' tidak di-insert, tetapi jika sudah ada di target status-nya ikut di-update.
|
| Definisi step:
|  source / target : nama tabel sumber (DB WRS Sales) & tujuan (DB default aplikasi)
|  key             : [kolom sumber, kolom target] — harus unik di sumber
|  columns         : [kolom target => kolom sumber]  (termasuk key)
|  insert_only     : kolom yang hanya diisi saat insert (tidak di-update)
|  insert_defaults : nilai tetap saat insert
|  update_defaults : nilai tetap saat update
|  skip_insert     : [kolom sumber, [nilai...]] — baris dengan nilai ini tidak di-insert (tetap di-update bila sudah ada)
*/

$same = fn (array $cols) => array_combine($cols, $cols);

return [

    'timezone' => env('SYNC_WRS_TIMEZONE', 'Asia/Jakarta'),

    // Jumlah baris yang dibaca dari sumber per blok
    'chunk' => (int) env('SYNC_WRS_CHUNK', 1000),

    // Batas waktu lock per job (detik). Jika proses mati, lock otomatis lepas setelah waktu ini.
    'lock_seconds' => (int) env('SYNC_WRS_LOCK_SECONDS', 4 * 3600),

    // Proses RUNNING tanpa progres (heartbeat) lebih dari ini dianggap mati → lock dilepas otomatis
    'stale_minutes' => (int) env('SYNC_WRS_STALE_MINUTES', 30),

    // Maksimal contoh baris gagal yang disimpan per step
    'max_error_samples' => 20,

    'jobs' => [
        'inventory' => [
            'label'      => 'Sync Inventory',
            'connection' => env('SYNC_WRS_INVENTORY_CONNECTION', 'WRS_SALES'),
            // Setiap hari jam 03:00 WIB
            'cron'       => env('SYNC_WRS_INVENTORY_CRON', '0 3 * * *'),
            'schedule'   => 'Setiap hari jam 03:00 WIB',
            'steps'      => ['color', 'model', 'model_color', 'samsat_jenis', 'samsat_model', 'samsat_type', 'type', 'spk_retail', 'inventory'],
        ],
        'faktur' => [
            'label'      => 'Sync Faktur',
            'connection' => env('SYNC_WRS_FAKTUR_CONNECTION', 'WRS_SALES'),
            // Mulai jam 07:00 WIB lalu setiap 1 jam (07:00, 08:00, ... 23:00)
            'cron'       => env('SYNC_WRS_FAKTUR_CRON', '0 7-23 * * *'),
            'schedule'   => 'Jam 07:00 WIB, lalu setiap 1 jam s/d 23:00',
            'steps'      => ['faktur'],
        ],
    ],

    'steps' => [

        'color' => [
            'label'           => 'Color',
            'legacy'          => 'sync_color.php',
            'source'          => 'tblcolor',
            'target'          => 'tblcolor',
            'key'             => ['kd_color', 'kd_color'],
            'columns'         => [
                'kd_color'        => 'kd_color',
                'nm_color_id'     => 'nm_color_id',
                'nm_color_global' => 'nm_color_global',
                'is_wrs'          => 'is_active',
            ],
            'insert_defaults' => ['is_wrs_aftersales' => true],
            'update_defaults' => ['is_wrs_aftersales' => true],
        ],

        'model' => [
            'label'           => 'Model',
            'legacy'          => 'sync_model.php',
            'source'          => 'tblmodel',
            'target'          => 'tblmodel',
            'key'             => ['kd_model', 'kd_model'],
            'columns'         => $same(['kd_model', 'nm_model', 'jenis_kendaraan', 'model_year', 'engine_type', 'vehicle_type', 'wmi', 'hand_over_picture', 'hand_over_picture_ext'])
                                 + ['is_wrs' => 'is_active'],
            'insert_defaults' => ['is_wrs_aftersales' => true],
        ],

        'model_color' => [
            'label'   => 'Model Color',
            'legacy'  => 'sync_model_color.php',
            'source'  => 'tblmodel_color',
            'target'  => 'tblmodel_color',
            'key'     => ['kd_model_color', 'kd_model_color'],
            'columns' => $same(['kd_model_color', 'fk_model', 'fk_color']),
        ],

        'samsat_jenis' => [
            'label'   => 'Samsat Jenis',
            'legacy'  => 'sync_type.php',
            'source'  => 'tblsamsat_jenis',
            'target'  => 'tblsamsat_jenis',
            'key'     => ['kd_samsat_jenis', 'kd_samsat_jenis'],
            'columns' => $same(['kd_samsat_jenis', 'nm_samsat_jenis', 'is_active']),
        ],

        'samsat_model' => [
            'label'   => 'Samsat Model',
            'legacy'  => 'sync_type.php',
            'source'  => 'tblsamsat_model',
            'target'  => 'tblsamsat_model',
            'key'     => ['kd_samsat_model', 'kd_samsat_model'],
            'columns' => $same(['kd_samsat_model', 'nm_samsat_model', 'is_active']),
        ],

        'samsat_type' => [
            'label'   => 'Samsat Type',
            'legacy'  => 'sync_type.php',
            'source'  => 'tblsamsat_type',
            'target'  => 'tblsamsat_type',
            'key'     => ['kd_samsat_type', 'kd_samsat_type'],
            'columns' => $same(['kd_samsat_type', 'nm_samsat_type', 'is_active']),
        ],

        'type' => [
            'label'           => 'Type',
            'legacy'          => 'sync_type.php',
            'source'          => 'tbltype',
            'target'          => 'tbltype',
            'key'             => ['kd_type', 'kd_type'],
            'columns'         => $same(['kd_type', 'nm_type', 'msc_code', 'fk_model', 'fk_samsat_model', 'fk_samsat_jenis', 'fk_samsat_type', 'isi_silinder', 'fuel', 'harga', 'tpt', 'sut', 'pk_id'])
                                 + ['is_wrs' => 'is_active'],
            'insert_defaults' => ['is_wrs_aftersales' => true],
        ],

        'spk_retail' => [
            'label'       => 'SPK Retail',
            'legacy'      => 'sync_spk.php',
            'source'      => 'tblspk_retail',
            'target'      => 'tblspk_retail',
            'key'         => ['no_spk_mmi', 'no_spk_mmi'],
            'columns'     => $same([
                'no_spk_mmi', 'tgl_spk_mmi', 'no_spk_dealer', 'tipe_spk', 'fk_dealer', 'fk_sales', 'tipe_hot_prospect',
                'fk_hot_prospect_perorangan', 'fk_hot_prospect_perusahaan', 'fk_hot_prospect_pemerintah',
                'fk_customer_perorangan', 'fk_customer_perusahaan', 'fk_customer_pemerintah', 'cara_byr', 'fk_leasing_company',
                'tenor', 'uang_muka', 'estimasi_tgl_from', 'estimasi_tgl_to', 'fk_sumber_customer', 'is_pemesan_sama_customer',
                'tipe_pemesan', 'pemesan_nm_perusahaan', 'pemesan_alamat_perusahaan', 'pemesan_kd_telp_perusahaan',
                'pemesan_no_telp_perusahaan', 'pemesan_no_npwp_perusahaan', 'pemesan_status_perusahaan',
                'pemesan_bidang_usaha_perusahaan', 'pemesan_contact_person_perusahaan', 'pemesan_nm_customer', 'pemesan_kd_telp',
                'pemesan_no_telp', 'pemesan_kd_hp', 'pemesan_no_hp', 'pemesan_alamat', 'pemesan_fk_kelurahan', 'pemesan_warga_negara',
                'pemesan_no_id', 'pemesan_no_kitas', 'pemesan_nm_negara', 'pemesan_nm_pemerintah', 'pemesan_alamat_pemerintah',
                'pemesan_kd_telp_pemerintah', 'pemesan_no_telp_pemerintah', 'pemesan_status_pemerintah',
                'pemesan_bidang_usaha_pemerintah', 'pemesan_contact_person_pemerintah', 'unit_fk_interior', 'unit_fk_model',
                'unit_fk_type', 'unit_fk_color', 'unit_aksesoris', 'unit_harga_satuan', 'is_stnk_sama_customer',
                'is_stnk_sama_pemesan', 'tipe_stnk', 'stnk_nm_perusahaan', 'stnk_alamat_perusahaan', 'stnk_kd_telp_perusahaan',
                'stnk_no_telp_perusahaan', 'stnk_no_npwp_perusahaan', 'stnk_status_perusahaan', 'stnk_bidang_usaha_perusahaan',
                'stnk_contact_person_perusahaan', 'stnk_nama', 'stnk_kd_telp', 'stnk_no_telp', 'stnk_kd_hp', 'stnk_no_hp',
                'stnk_alamat', 'stnk_fk_kelurahan', 'stnk_warga_negara', 'stnk_no_id', 'stnk_no_kitas', 'stnk_nm_negara',
                'stnk_nm_pemerintah', 'stnk_alamat_pemerintah', 'stnk_kd_telp_pemerintah', 'stnk_no_telp_pemerintah',
                'stnk_status_pemerintah', 'stnk_bidang_usaha_pemerintah', 'stnk_contact_person_pemerintah', 'fk_spk_fleet',
                'fk_spk_fleet_unit', 'fk_user_approval', 'tgl_approval', 'status_approval', 'note_approval', 'status',
            ]),
            'skip_insert' => ['status', ['Canceled']],
        ],

        'inventory' => [
            'label'       => 'Inventory',
            'legacy'      => 'sync_inventory.php',
            'source'      => 'tblinventory',
            'target'      => 'tblinventory',
            // Script lama mencocokkan no_vin + fk_dealer, tapi kombinasi itu tidak unik di sumber → pakai pk_id (unik).
            'key'         => ['pk_id', 'pk_id'],
            'columns'     => $same([
                'pk_id', 'no_vin', 'no_mesin', 'fk_dealer', 'fk_type', 'fk_model', 'fk_color', 'fk_csi', 'tahun_pembuatan',
                'formulir_ab', 'pib', 'tpt', 'sut', 'fk_cross', 'fk_faktur', 'is_active', 'fk_spk', 'srut', 'csi_fk_color',
                'status', 'tgl_print_do', 'tgl_print_serah_terima', 'tgl_received', 'fk_customer_perorangan',
                'fk_customer_perusahaan', 'fk_customer_pemerintah', 'use_by', 'use_id', 'fk_inventory_adjustment',
            ]),
            'skip_insert' => ['status', ['Canceled']],
        ],

        'faktur' => [
            'label'       => 'Faktur',
            'legacy'      => 'sync_faktur.php',
            'source'      => 'tblfaktur',
            'target'      => 'tblfaktur',
            'key'         => ['no_faktur_request', 'no_faktur_request'],
            // Kolom milik After Sales (fk_dealer_handover, tanggal_handover) tidak disentuh.
            'columns'     => $same([
                'no_faktur_request', 'tgl_faktur_request', 'fk_dealer', 'is_sama_customer', 'is_sama_pemilik', 'is_sama_pemesan',
                'pemakai_nm_customer', 'pemakai_kd_telp', 'pemakai_no_telp', 'pemakai_kd_hp', 'pemakai_no_hp', 'pemakai_alamat',
                'pemakai_fk_kelurahan', 'pemakai_tgl_lahir', 'pemakai_jenis_kelamin', 'pemakai_status_perkawinan', 'pemakai_agama',
                'pemakai_warga_negara', 'pemakai_no_id', 'pemakai_no_kitas', 'pemakai_nm_negara', 'pemakai_fk_occupation',
                'pemakai_penghasilan_perbulan', 'pemakai_status', 'pemakai_status_mobil', 'pemakai_jml_mobil_sebelumnya',
                'pemakai_merek_tambahan1', 'pemakai_type_tambahan1', 'pemakai_merek_tambahan2', 'pemakai_type_tambahan2',
                'pemakai_merek_tambahan3', 'pemakai_type_tambahan3', 'pemakai_merek_pengganti', 'pemakai_type_pengganti',
                'pemakai_alasan', 'pemakai_alasan_lainnya', 'fk_spk', 'fk_vin', 'fk_model', 'fk_type', 'fk_color',
                'tgl_berlaku_faktur', 'alasan_reprint', 'fk_customer_perorangan', 'fk_customer_perusahaan',
                'fk_customer_pemerintah', 'fk_user_tandatangan_faktur', 'tgl_print_faktur', 'tgl_direct', 'kita_pickup',
                'cimb_sent', 'remark', 'fk_user_approval', 'tgl_approval', 'status_approval', 'note_approval', 'status',
            ]),
            // Mengikuti sync_faktur.php lama: tgl lahir & jenis kelamin pemakai hanya diisi saat insert
            // (nilainya bisa sudah dikoreksi di sisi After Sales).
            'insert_only' => ['pemakai_tgl_lahir', 'pemakai_jenis_kelamin'],
            'skip_insert' => ['status', ['Canceled']],
        ],
    ],
];

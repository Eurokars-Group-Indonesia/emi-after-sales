# Sync WRS Sales → WRS Connect After Sales

Menggantikan script lama `sync_color.php`, `sync_model.php`, `sync_model_color.php`, `sync_type.php`,
`sync_spk.php`, `sync_inventory.php` dan `sync_faktur.php`.

## Ringkasan

| Job | Jadwal (WIB) | Tabel (urutan) |
|---|---|---|
| `inventory` | setiap hari 03:00 | tblcolor, tblmodel, tblmodel_color, tblsamsat_jenis, tblsamsat_model, tblsamsat_type, tbltype, tblspk_retail, tblinventory |
| `faktur` | 07:00 lalu tiap jam s/d 23:00 | tblfaktur |

- Sumber: database di tabel `ms_sync_db_connection` (baris `WRS_SALES`), dikelola di menu
  **Administration › Utility › Koneksi Database Sync**. Password tersimpan terenkripsi dengan `APP_KEY`.
- Target: database default aplikasi (MySQL).
- **Tidak ada DELETE.** Hanya insert baris baru & update kolom yang berubah. Baris yang hilang di sumber dibiarkan.
- Baris berstatus `Canceled` tidak di-insert, tetapi kalau sudah ada di target status-nya ikut di-update.
- Definisi kolom per tabel ada di `config/sync_wrs.php`.

## Cara kerja

1. Scheduler (`php artisan schedule:work`) memanggil `sync-wrs:run inventory|faktur --trigger=schedule`.
2. Job mengambil Cache lock `sync-wrs:{job}` → satu job hanya jalan satu proses. Jadwal yang bentrok dicatat `SKIPPED`.
3. Untuk setiap tabel: sumber dibaca per 1.000 baris (keyset pagination berdasarkan key), dicocokkan dengan target,
   lalu ditulis per blok dalam 1 transaksi. Jika satu blok gagal, blok dibagi dua terus sampai baris bermasalah
   ketemu; baris lain tetap masuk, baris gagal dicatat (maks 20 contoh) → status `PARTIAL`.
4. Hasil dicatat di `tr_sync_wrs_log` (per eksekusi) & `tr_sync_wrs_log_detail` (per tabel), tampil di menu
   **Utility › Monitoring Sync WRS Sales › Sync Inventory / Sync Faktur**.
5. Tombol **Jalankan Sekarang** tidak menjalankan sync di web request; ia membuat log `QUEUED` yang dieksekusi
   `sync-wrs:run --queued` (dijadwalkan tiap menit).

Aturan kompatibilitas dengan script lama: string `''` dan `'0'` disimpan `NULL` (kecuali kolom NOT NULL);
`pemakai_tgl_lahir` & `pemakai_jenis_kelamin` di tblfaktur hanya diisi saat insert; kolom After Sales
(`fk_dealer_handover`, `tanggal_handover`) tidak disentuh. Nilai numerik di luar jangkauan `DECIMAL(20,2)`
disimpan NULL dan dicatat sebagai peringatan di detail log.

## Perintah

```bash
php artisan sync-wrs:run faktur --dry-run     # hitung insert/update + contoh perubahan, tanpa menulis
php artisan sync-wrs:run inventory            # jalankan manual dari console
php artisan sync-wrs:run inventory --unlock   # lepas lock jika proses mati di tengah jalan
php artisan schedule:list                     # cek jadwal & next due
```

## Docker

`docker/supervisord.conf` sekarang punya program `laravel-scheduler` (`php artisan schedule:work`, user www-data,
log di `storage/logs/scheduler.log`). Karena file itu di-COPY saat build, image harus di-build ulang:

```bash
docker compose up -d --build app
docker exec -it emi_retention_app supervisorctl status          # php-fpm & laravel-scheduler RUNNING
docker exec -it emi_retention_app php artisan schedule:list
```

Catatan: sebelum ini container tidak menjalankan scheduler sama sekali, jadi Email Scheduler CKD (16:00)
juga baru akan jalan setelah perubahan ini (kecuali selama ini ada cron di host — jika ya, matikan salah satunya
agar tidak dobel).

## Maintenance

- **Pantau** menu monitoring: status `FAILED`/`PARTIAL`, kolom Gagal, dan pesan peringatan.
- **Ganti host/password DB WRS Sales**: ubah di menu Koneksi Database Sync, klik *Test Koneksi*. Tidak perlu redeploy.
- **APP_KEY diganti** → password koneksi tidak bisa didekripsi; isi ulang password di menu.
- **Container restart di tengah sync** → log tetap `RUNNING`. Jika tidak ada progres > 30 menit (`SYNC_WRS_STALE_MINUTES`),
  run berikutnya otomatis melepas lock & menandai log lama `FAILED`. Bisa juga manual dengan `--unlock`.
- **Ubah jadwal**: `.env` / `.env.docker` → `SYNC_WRS_INVENTORY_CRON`, `SYNC_WRS_FAKTUR_CRON`, lalu restart container
  (entrypoint menjalankan `config:cache`).
- **Tambah kolom yang disinkron**: tambah kolom di migration baru (tanpa drop), lalu tambahkan di `config/sync_wrs.php`.
- Tabel log tidak pernah dibersihkan otomatis (±20 baris/hari) sesuai aturan tanpa hapus data.
- **Sumber data**: tblspk_retail dan tblfaktur **tidak pernah dibuat di After Sales** — sumbernya selalu WRS Sales,
  dan di WRS Connect keduanya hanya salinan hasil sync. Karena itu nomor dokumen (no_spk_mmi, no_faktur_request)
  tidak akan bentrok dengan data lokal.
- **Document Numbering**: tblmodel_color, tbltype (pk_id), tblspk_retail, tblinventory (pk_id) sudah terdaftar.
  Nomor lokal otomatis melewati nomor yang sudah ada hari ini.

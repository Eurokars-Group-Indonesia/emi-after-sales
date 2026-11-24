# Sync WRS Connect After Sales → MSI (Customer CSI)

Menggantikan script lama `upload_customer_csi.php` (PHP 5 + `COM("ADODB.Connection")`, hanya jalan di Windows
dan tidak bisa dipakai di PHP 8.4).

## Ringkasan

| | |
|---|---|
| Jadwal | Setiap hari **21:00 WIB** (`SYNC_MSI_CRON`, default `0 21 * * *`) |
| Sumber | Database WRS Connect (MySQL): `tblcustomer_log`, `tblcustomer`, `tblkpi`, `tblwork_planning`, `ms_religion`, `tblinventory`, `afsel_dealer_personel`, `tblfaktur` |
| Tujuan | Database MSI (SQL Server) `dbo.members` — koneksi baris **MSI** di menu **Utility › Koneksi Database Sync** |
| Parameter | `log_action_date >= {dari} 00:00:00 and log_action_date <= {sampai} 23:59:59`; default `dari` = `tblsetting.last_update_csi`, `sampai` = hari ini |
| Filter | `tblsetting.filter_csi` (potongan WHERE, nama kolom sama seperti script lama) |
| Monitoring | Menu **Utility › Monitoring Sync MSI (CSI)** (permission `sync-msi.view`, jalankan manual `sync-msi.run`) |

**Tidak ada DELETE di MSI.** Hanya `SELECT`, `INSERT` dan `UPDATE` ke `dbo.members`.

## Alur per baris (sama dengan script lama)

1. Dealer (`dbo.dealers.dealer_code`) dan user log (`dbo.users.kd_user_wrs = tblcustomer_log.log_action_userid`)
   harus terdaftar **tepat satu kali** di MSI. Kalau tidak → baris **dilewati** (dicatat di detail log).
2. Belum ada di `dbo.members` (vin + dealer_code + handover_date) → `INSERT` (type `CSI`, `dbo.fncUniqueCodeGenerator(10)`),
   sudah ada → `UPDATE ... where vin, handover_date, service_dealer_id, type='CSI'`.
3. `reason_for_service` & `next_service_date` dari kategori service KPI (`config/sync_msi.php` → `service_map`):
   PDI +30 hari, FS1 +150, FS2/MAINTENANCE/OTHER +180, WARRANTY kosong.
4. Setiap baris ditulis dalam transaksinya sendiri; error → baris itu di-rollback, baris lain tetap jalan.
   50 baris berturut-turut gagal (mis. koneksi putus) → sync dihentikan.

## Aturan commit di WRS Connect (perilaku lama)

- `tblkpi.uploaded_to_msi = 1` dan `tblsetting.last_update_csi = hari ini` **hanya** diubah jika ada baris yang diproses
  dan **semua** sukses. Satu baris saja dilewati / gagal → keduanya tidak diubah, rentang yang sama dikirim ulang
  di jadwal berikutnya (INSERT/UPDATE ke MSI aman diulang).
- Tidak ada data di rentang → `last_update_csi` tidak diubah (sama dengan script lama).
- Rentang **manual** (tanggal dipilih sendiri) tidak pernah memajukan `last_update_csi`; `uploaded_to_msi` tetap di-set bila semua sukses.

## Perbedaan teknis dari script lama

- Nilai dikirim sebagai parameter (bukan disambung ke SQL). Tanda petik `'` tetap diganti spasi agar data sama dengan sebelumnya.
- Kolom tanggal bertipe date/datetime di MSI dikirim dalam format `YYYYMMDD HH:MM:SS` (tidak bergantung setting bahasa
  SQL Server); kolom bertipe teks tetap memakai format string lama. `next_service_date` tetap jam 12:00:00 seperti hasil script lama.
- Bug variabel "nyangkut" dari baris sebelumnya (`$vin_msi`, `$c_id_model`) tidak ikut terbawa: cek member & model dilakukan per baris.
- `last_odo_meter` decimal MySQL (`12345.00`) dikirim sebagai `12345`.
- Customer tanpa `tanggal_last_service` tidak lagi membatalkan seluruh proses (dulu memicu error SQL di PostgreSQL).

## Tabel baru

| Tabel | Keterangan |
|---|---|
| `tblsetting` | Dari WRS lama. Data awal: `php artisan db:seed --class=TblSettingSeeder` (1 baris, hanya jika kosong) |
| `tblcustomer_log` | Snapshot customer per aksi. `pk_id_log` dari Document Numbering `tblcustomer_log` |
| `tblwork_planning` | `kd_work_planning` dari Document Numbering `tblwork_planning`, `pk_id` dari `tblwork_planning_pk_id` (reset harian) |
| `tr_sync_msi_log` / `_detail` | Log eksekusi & baris yang dilewati / gagal (baris sukses jika `SYNC_MSI_LOG_SUCCESS_ROWS=true`) |

> Pengisian oleh **Upload Service (Dealer)** (`App\Services\Afsel\ServiceUploadService`), dalam satu transaksi:
> - `tblcustomer_log` lewat `App\Services\Afsel\CustomerLogService`: 1 snapshot per customer SESUDAH perubahan,
>   `log_action_mode` `I` (customer baru) / `UA` (update customer lama), `log_action_userid` = `session('user.id')`
>   (`kd_dealer_user`), `log_action_username` = `session('user.username')`. Fitur lain yang mengubah `tblcustomer`
>   wajib memanggil `CustomerLogService::log()` di transaksi yang sama.
> - `tblcustomer`: Nama STNK → `nama_customer`, Nama Pemakai → `nm_pemakai`, Email → `email`, Oddometer → `last_odo_meter`.
>   Customer baru: `tanggal_penyerahan` = `tblfaktur.tanggal_handover` (terbaru per VIN; kosong → tanggal upload).
>   Customer lama: semua data Excel ditulis hanya jika Tanggal Service >= `tanggal_last_service`; kolom opsional
>   (Telp Rumah, Email, Agama, Tanggal Lahir) yang kosong tidak menimpa data; `tanggal_last_service` tidak pernah mundur.
> - `tblwork_planning`: 1 baris per baris Excel. `fk_service_advisor` / `fk_technician` = `dealer_personel_id` dari
>   Nama SA / Nama Teknisi, tanggal = Tanggal Service, `status_1` = `Upload Service`, `status` = `Finish`.
>   `fk_group_technician` diisi teknisi yang sama (belum ada master grup teknisi). `tblkpi.fk_work_planning` ikut diisi.
> - `tblkpi.service_type` = Kode Service, `fk_category_1/2_vehicles` sesuai `sync_msi.service_map` (sebelumnya NULL + OTHER).

## Perintah

```bash
php artisan migrate
php artisan db:seed --class=TblSettingSeeder
php artisan sync-msi:run --dry-run                          # baca MSI & hitung insert/update/skip, tanpa menulis apa pun
php artisan sync-msi:run                                    # rentang default
php artisan sync-msi:run --from=2026-08-01 --to=2026-08-31  # rentang manual
php artisan sync-msi:run --unlock                           # lepas lock jika proses mati
php artisan schedule:list
```

Menu **Jalankan Manual** tidak menjalankan sync di web request: ia membuat log `QUEUED` yang dieksekusi
`sync-msi:run --queued` (scheduler tiap menit).

## Docker / server

- `Dockerfile` memasang **Microsoft ODBC Driver 18** + extension `sqlsrv`/`pdo_sqlsrv` → build ulang image:
  `docker compose up -d --build app`, cek `docker exec -it emi_retention_app php -m | grep sqlsrv`.
- ODBC 18 default `Encrypt=yes`. Server MSI tanpa TLS → `SYNC_MSI_SQLSRV_ENCRYPT=no` (default). Jika MSI memakai TLS,
  set `yes` (dan `SYNC_MSI_SQLSRV_TRUST_CERT=false` jika sertifikatnya valid).
- Koneksi MSI: tambah di menu Koneksi Database Sync (Name `MSI`, Driver SQL Server, port 1433) lalu **Test Koneksi**,
  atau isi `.env` `DB_MSI_*` sebelum `php artisan migrate` (dipakai sekali untuk membuat baris awal).

## Maintenance

- Pantau status `PARTIAL` / `FAILED` dan kolom **Dilewati**. Penyebab umum: dealer / user WRS belum didaftarkan di MSI.
  Setelah didaftarkan, jadwal berikutnya otomatis mengirim ulang (last_update_csi belum maju).
- Ubah filter CSI: update `tblsetting.filter_csi`. Kolom yang bisa dipakai: kolom hasil query di
  `App\Services\SyncMsi\CsiCustomerSource::query()` (mis. `type_customer`, `service_type`, `fk_model`, `tanggal_handover`, `dealer_wrs`).
- Container restart di tengah sync → log tertinggal `RUNNING`; run berikutnya melepas lock otomatis setelah
  `SYNC_MSI_STALE_MINUTES` (30 menit), atau manual `--unlock`.
- Tabel log tidak pernah dibersihkan otomatis (aturan tanpa hapus data).

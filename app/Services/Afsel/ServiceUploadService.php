<?php

namespace App\Services\Afsel;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Upload Service (Dealer): Excel → tblcustomer (insert / update) + tblcustomer_log (snapshot)
 * + tblwork_planning & tblkpi (selalu insert, 1 baris Excel = 1 work planning + 1 kpi).
 *
 * Aturan:
 *  - Semua baris divalidasi dulu. Jika ada SATU saja baris yang error, TIDAK ADA data yang disimpan (rollback);
 *    baris yang error harus dikeluarkan/diperbaiki di Excel lalu diupload ulang.
 *  - VIN harus sudah delivery: ada di tblfaktur (WRS Sales) dengan status_bast = 'TerBast'.
 *  - Customer dicari per (fk_dealer, no_vin). Tidak ada → insert customer baru (tanggal_penyerahan dari
 *    tblfaktur.tanggal_handover). Ada → data customer diperbarui dari Excel (lihat updateExistingCustomer).
 *  - Nama STNK → nama_customer, Nama Pemakai → nm_pemakai.
 *  - Kode customer & kpi memakai DocumentNumberService (yyyymmdd + 20 digit, reset harian).
 */
class ServiceUploadService
{
    public const SERVICE_CODES    = ['PDI', 'FS1', 'FS2', 'WARRANTY', 'MAINTENANCE', 'OTHER'];
    public const VEHICLE_BRINGERS = ['Owner', 'Driver'];
    public const LEVEL_SA         = 'Service Advisor';
    public const LEVEL_TECHNICIAN = 'Technician';
    public const DEFAULT_CATEGORY = '2012061900000000000000000001';
    public const BAST_DELIVERED   = 'TerBast';

    /** key internal => judul kolom di template (urutan = urutan kolom template). */
    public const COLUMNS = [
        'no_polisi'        => 'No Polisi',
        'no_vin'           => 'No Vin',
        'tanggal_service'  => 'Tanggal Service',
        'odometer'         => 'Oddometer',
        'no_ro'            => 'No Repair Order',
        'customer_request' => 'Service Permintaan Pelanggan',
        'kode_service'     => 'Kode Service',
        'nama_sa'          => 'Nama SA',
        'nama_teknisi'     => 'Nama Teknisi',
        'pembawa'          => 'Yang Membawa Kendaraan',
        'nama_pemakai'     => 'Nama Pemakai',
        'nama_stnk'        => 'Nama STNK',
        'alamat'           => 'Alamat',
        'telp_rumah'       => 'Telp Rumah',
        'no_hp'            => 'No HP',
        'email'            => 'Email',
        'agama'            => 'Agama',
        'tanggal_lahir'    => 'Tanggal Lahir',
    ];

    /** Judul kolom alternatif (sudah dinormalisasi) yang juga diterima. */
    private const HEADER_ALIASES = [
        'odometer'   => 'odometer',
        'no. polisi' => 'no_polisi',
        'no. vin'    => 'no_vin',
        'no. hp'     => 'no_hp',
    ];

    /** Kolom nomor telepon: dibaca sesuai tampilan sel agar angka 0 di depan (format "0"#) tidak hilang. */
    private const PHONE_COLUMNS = ['telp_rumah', 'no_hp'];

    /** Panjang maksimal mengikuti kolom tujuan di tblcustomer. */
    private const MAX_LENGTH = [
        'no_polisi'    => 30,
        'no_vin'       => 22,
        'pembawa'      => 255,
        'nama_pemakai' => 110,
        'nama_stnk'    => 110,
        'telp_rumah'   => 150,
        'no_hp'        => 150,
        'email'        => 151,
    ];

    /** Nilai tblwork_planning untuk service hasil upload (servis sudah selesai saat diupload). */
    public const WORK_PLANNING_STATUS_1 = 'Upload Service';
    public const WORK_PLANNING_STATUS   = 'Finish';

    public function __construct(private DocumentNumberService $numbers, private CustomerLogService $customerLog)
    {
    }

    /**
     * @return array{status: bool, message: string, total: int, errors: array, summary: array}
     */
    public function process(string $path, string $kdDealer): array
    {
        $rows = $this->readRows($path);   // InvalidArgumentException untuk error level file

        $context = $this->loadMasterData($kdDealer);
        [$clean, $errors] = $this->validateRows($rows, $context);

        if ($clean) {
            $this->validateAgainstDatabase($clean, $errors, $kdDealer);
        }

        if ($errors) {
            ksort($errors);

            return [
                'status'  => false,
                'message' => count($errors) . ' dari ' . count($rows) . ' baris tidak valid. Tidak ada data yang disimpan. '
                    . 'Perbaiki atau keluarkan baris tersebut dari Excel, lalu upload ulang.',
                'total'   => count($rows),
                'errors'  => collect($errors)->map(fn ($messages, $rowNo) => [
                    'row'       => $rowNo,
                    'no_polisi' => $rows[$rowNo]['no_polisi'] ?? '',
                    'no_vin'    => $rows[$rowNo]['no_vin'] ?? '',
                    'messages'  => array_values(array_unique($messages)),
                ])->values()->all(),
                'summary' => [],
            ];
        }

        $summary = $this->save($clean, $kdDealer);

        return [
            'status'  => true,
            'message' => count($clean) . ' data service berhasil diupload.',
            'total'   => count($rows),
            'errors'  => [],
            'summary' => $summary,
        ];
    }

    // =========================================================================
    // Baca Excel
    // =========================================================================

    /**
     * @return array<int, array<string, mixed>> nomor baris Excel => [key => nilai mentah]
     */
    private function readRows(string $path): array
    {
        // .xlsx = arsip zip; tanpa ekstensi PHP zip file tidak bisa dibaca sama sekali
        if (!class_exists(\ZipArchive::class)) {
            report(new \RuntimeException('PHP extension "zip" is not enabled; Upload Service cannot read .xlsx files.'));
            throw new InvalidArgumentException('Server belum dapat membaca file .xlsx (ekstensi PHP zip belum aktif). Hubungi admin aplikasi.');
        }

        // Hanya .xlsx: reader Xlsx dipakai langsung, jadi file .xls yang di-rename menjadi .xlsx tetap ditolak
        $reader = IOFactory::createReader('Xlsx');
        if (!$reader->canRead($path)) {
            throw new InvalidArgumentException('File harus berformat .xlsx. File .xls tidak didukung, simpan ulang (Save As) sebagai Excel Workbook (.xlsx).');
        }

        try {
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($path);
        } catch (Throwable $e) {
            throw new InvalidArgumentException('File tidak dapat dibaca. Pastikan file .xlsx sesuai template.');
        }

        $sheet = $spreadsheet->getSheetByName('Data') ?? $spreadsheet->getSheet(0);

        // ---- header (baris 1) ----
        $columns = [];
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        for ($c = 1; $c <= $highestCol; $c++) {
            $label = $this->normalizeHeader($this->toText($this->cellValue($sheet, $c, 1)));
            $key   = $this->headerKey($label);
            if ($key && !isset($columns[$key])) {
                $columns[$key] = $c;
            }
        }

        $missing = array_diff_key(self::COLUMNS, $columns);
        if ($missing) {
            throw new InvalidArgumentException('Kolom template tidak lengkap / tidak sesuai: ' . implode(', ', $missing) . '. Gunakan template upload service terbaru.');
        }

        // ---- data ----
        $maxRows = (int) config('afsel.service_upload.max_rows', 2000);
        $rows = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($r = 2; $r <= $highestRow; $r++) {
            $row = [];
            $filled = false;

            foreach ($columns as $key => $c) {
                $cell  = $sheet->cellExists([$c, $r]) ? $sheet->getCell([$c, $r]) : null;
                $value = $this->rawValue($cell);

                if ($value !== null && !is_float($value) && !is_int($value) && !($value instanceof \DateTimeInterface)) {
                    $value = $this->toText($value);
                } elseif ((is_float($value) || is_int($value)) && in_array($key, self::PHONE_COLUMNS, true)) {
                    $value = $this->toText($value, $cell);
                }

                if ($value !== null && $value !== '') {
                    $filled = true;
                }
                $row[$key] = $value;
            }

            if (!$filled) {
                continue;   // baris kosong dilewati
            }

            $row['no_polisi'] = $this->toText($row['no_polisi']);
            $row['no_vin']    = $this->toText($row['no_vin']);
            $rows[$r] = $row;

            if (count($rows) > $maxRows) {
                throw new InvalidArgumentException("Maksimal {$maxRows} baris data per upload. Silakan bagi file menjadi beberapa bagian.");
            }
        }

        if (!$rows) {
            throw new InvalidArgumentException('File tidak berisi data. Isi data mulai baris ke-2 sesuai template.');
        }

        return $rows;
    }

    private function cellValue(Worksheet $sheet, int $col, int $row): mixed
    {
        return $sheet->cellExists([$col, $row]) ? $this->rawValue($sheet->getCell([$col, $row])) : null;
    }

    private function rawValue(?Cell $cell): mixed
    {
        if (!$cell) {
            return null;
        }

        $value = $cell->getDataType() === DataType::TYPE_FORMULA ? $cell->getOldCalculatedValue() ?? $cell->getCalculatedValue() : $cell->getValue();

        if ($value instanceof RichText) {
            $value = $value->getPlainText();
        }

        return $value;
    }

    /** Nilai sel → teks yang sudah di-trim. Angka bulat tidak menjadi "1.0E+10" atau "123.0". */
    private function toText(mixed $value, ?Cell $cell = null): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_int($value) || is_float($value)) {
            $plain = (floor($value) == $value && abs($value) < 1e15) ? sprintf('%.0f', $value) : (string) $value;
            $format = $cell ? $cell->getStyle()->getNumberFormat()->getFormatCode() : 'General';

            if ($format !== 'General') {
                // Format angka + teks tetap, mis. "0"# (template WRS untuk No HP) atau 000000000000.
                // PhpSpreadsheet membuang teks "0" di depan, jadi diterapkan manual.
                if (preg_match('/^(?:"([^"]*)"|\\\\(.))?([#0]+)(?:"([^"]*)")?$/', $format, $m)) {
                    return ($m[1] ?? '') . ($m[2] ?? '') . str_pad($plain, substr_count($m[3], '0'), '0', STR_PAD_LEFT) . ($m[4] ?? '');
                }
                $formatted = $this->trim($cell->getFormattedValue());
                if ($formatted !== '') {
                    return $formatted;
                }
            }

            return $plain;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('m/d/Y');
        }

        return $this->trim((string) $value);
    }

    private function trim(string $value): string
    {
        return preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $value) ?? trim($value);
    }

    private function normalizeHeader(string $label): string
    {
        return strtolower(preg_replace('/\s+/u', ' ', $label));
    }

    private function headerKey(string $normalized): ?string
    {
        static $map = null;
        $map ??= collect(self::COLUMNS)->mapWithKeys(fn ($label, $key) => [$this->normalizeHeader($label) => $key])->all()
            + self::HEADER_ALIASES;

        return $map[$normalized] ?? null;
    }

    // =========================================================================
    // Validasi
    // =========================================================================

    private function loadMasterData(string $kdDealer): array
    {
        $levelIds = DB::table('afsel_dealer_level_personel')
            ->whereIn('dealer_level_personel', [self::LEVEL_SA, self::LEVEL_TECHNICIAN])
            ->pluck('dealer_level_personel_id', 'dealer_level_personel');

        $personel = DB::table('afsel_dealer_personel')
            ->where('fk_dealer', $kdDealer)
            ->where('is_active', true)
            ->whereIn('dealer_level_personel_id', $levelIds->values())
            ->orderByDesc('dealer_personel_id')
            ->get(['dealer_personel_id', 'name', 'dealer_level_personel_id']);

        // nama => dealer_personel_id. Key array PHP case-sensitive → nama harus sama persis huruf besar/kecilnya.
        // Nama kembar di level yang sama: dipakai dealer_personel_id terkecil (urutan desc, yang terakhir menang).
        $namesOf = fn (string $level) => $personel
            ->where('dealer_level_personel_id', $levelIds[$level] ?? '__none__')
            ->mapWithKeys(fn ($p) => [$this->trim((string) $p->name) => (string) $p->dealer_personel_id])
            ->all();

        return [
            'sa'         => $namesOf(self::LEVEL_SA),
            'technician' => $namesOf(self::LEVEL_TECHNICIAN),
            'religions'  => DB::table('ms_religion')->where('is_active', true)->pluck('religion_id', 'religion')->all(),
            'today'      => now()->startOfDay(),
        ];
    }

    /**
     * Validasi format per baris (tanpa query per baris).
     *
     * @return array{0: array<int, array>, 1: array<int, string[]>}
     */
    private function validateRows(array $rows, array $ctx): array
    {
        $clean  = [];
        $errors = [];
        $today  = $ctx['today'];

        foreach ($rows as $rowNo => $row) {
            $err = [];
            $text = fn (string $key) => $this->toText($row[$key] ?? null);

            // No Polisi & No VIN: wajib, huruf besar + angka, tanpa spasi / karakter lain
            foreach (['no_polisi' => 'No Polisi', 'no_vin' => 'No Vin'] as $key => $label) {
                $v = $text($key);
                if ($v === '') {
                    $err[] = "{$label} is required.";
                } elseif (!preg_match('/^[A-Z0-9]+$/', $v)) {
                    $err[] = "{$label} \"{$v}\" must contain uppercase letters and numbers only (no spaces or symbols).";
                }
            }

            // Tanggal Service: wajib, mm/dd/yyyy, tidak boleh lebih dari hari ini
            $serviceDate = $this->parseDate($row['tanggal_service'] ?? null, 'Tanggal Service', $err, true);
            if ($serviceDate && $serviceDate->gt($today)) {
                $err[] = 'Tanggal Service ' . $serviceDate->format('m/d/Y') . ' cannot be a future date (server date: ' . $today->format('m/d/Y') . ').';
            }

            // Oddometer: wajib & angka
            $odometer = null;
            $odoRaw = $row['odometer'] ?? null;
            if ($odoRaw === null || $odoRaw === '') {
                $err[] = 'Oddometer is required.';
            } elseif ((is_int($odoRaw) || is_float($odoRaw)) ? floor($odoRaw) == $odoRaw : preg_match('/^\d+$/', (string) $odoRaw)) {
                // Bilangan bulat saja: "12.000" ambigu (12 atau 12 ribu km) sehingga ditolak
                $odometer = (float) $odoRaw;
                if ($odometer < 0 || $odometer > 99999999) {
                    $err[] = 'Oddometer must be between 0 and 99,999,999.';
                }
            } else {
                $err[] = "Oddometer \"{$odoRaw}\" must be a whole number without separators (e.g. 12000).";
            }

            // Kolom teks wajib
            foreach (['no_ro' => 'No Repair Order', 'customer_request' => 'Service Permintaan Pelanggan',
                      'nama_pemakai' => 'Nama Pemakai', 'nama_stnk' => 'Nama STNK', 'alamat' => 'Alamat', 'no_hp' => 'No HP'] as $key => $label) {
                if ($text($key) === '') {
                    $err[] = "{$label} is required.";
                }
            }

            // Kode Service
            $kode = $text('kode_service');
            if ($kode === '') {
                $err[] = 'Kode Service is required.';
            } elseif (!in_array($kode, self::SERVICE_CODES, true)) {
                $err[] = "Kode Service \"{$kode}\" is invalid. Allowed values (uppercase): " . implode(', ', self::SERVICE_CODES) . '.';
            }

            // Nama SA & Nama Teknisi: personel aktif di dealer ini, nama sama persis
            foreach (['nama_sa' => ['Nama SA', 'sa', self::LEVEL_SA], 'nama_teknisi' => ['Nama Teknisi', 'technician', self::LEVEL_TECHNICIAN]] as $key => [$label, $ctxKey, $level]) {
                $v = $text($key);
                if ($v === '') {
                    $err[] = "{$label} is required.";
                } elseif (!isset($ctx[$ctxKey][$v])) {
                    $err[] = "{$label} \"{$v}\" is not an active {$level} in your dealer personnel master (name must match exactly, case-sensitive).";
                }
            }

            // Yang Membawa Kendaraan
            $pembawa = $text('pembawa');
            if ($pembawa === '') {
                $err[] = 'Yang Membawa Kendaraan is required.';
            } elseif (!in_array($pembawa, self::VEHICLE_BRINGERS, true)) {
                $err[] = "Yang Membawa Kendaraan \"{$pembawa}\" is invalid. Allowed values: Owner or Driver.";
            }

            // Agama (opsional)
            $agama = $text('agama');
            $religionId = null;
            if ($agama !== '') {
                $religionId = $ctx['religions'][$agama] ?? null;
                if (!$religionId) {
                    $err[] = "Agama \"{$agama}\" is invalid. Allowed values: " . implode(', ', array_keys($ctx['religions'])) . '.';
                }
            }

            // Tanggal Lahir (opsional)
            $birthDate = $this->parseDate($row['tanggal_lahir'] ?? null, 'Tanggal Lahir', $err, false);
            if ($birthDate && $birthDate->gt($today)) {
                $err[] = 'Tanggal Lahir cannot be a future date.';
            }

            // Panjang maksimal kolom tujuan
            foreach (self::MAX_LENGTH as $key => $max) {
                if (mb_strlen($text($key)) > $max) {
                    $err[] = self::COLUMNS[$key] . " must not exceed {$max} characters.";
                }
            }

            if ($err) {
                $errors[$rowNo] = $err;
                continue;
            }

            $clean[$rowNo] = [
                'no_polisi'        => $text('no_polisi'),
                'no_vin'           => $text('no_vin'),
                'service_date'     => $serviceDate,
                'odometer'         => $odometer,
                'no_ro'            => $text('no_ro'),
                'customer_request' => $text('customer_request'),
                'kode_service'     => $kode,
                'nama_sa'          => $text('nama_sa'),
                'nama_teknisi'     => $text('nama_teknisi'),
                'sa_id'            => $ctx['sa'][$text('nama_sa')],
                'technician_id'    => $ctx['technician'][$text('nama_teknisi')],
                'pembawa'          => $pembawa,
                'nama_pemakai'     => $text('nama_pemakai'),
                'nama_stnk'        => $text('nama_stnk'),
                'alamat'           => $text('alamat'),
                'telp_rumah'       => $text('telp_rumah'),
                'no_hp'            => $text('no_hp'),
                'email'            => $text('email'),
                'religion_id'      => $religionId,
                'birth_date'       => $birthDate,
            ];
        }

        return [$clean, $errors];
    }

    /**
     * Tanggal dari sel Excel: tanggal asli Excel (serial number) atau teks mm/dd/yyyy.
     */
    private function parseDate(mixed $value, string $label, array &$err, bool $required): ?Carbon
    {
        if ($value === null || $value === '') {
            if ($required) {
                $err[] = "{$label} is required.";
            }
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value->format('Y-m-d'))->startOfDay();
        }

        if (is_int($value) || is_float($value)) {
            // 1 = 01/01/1900, 2958465 = 12/31/9999
            if ($value >= 1 && $value <= 2958465) {
                return Carbon::parse(ExcelDate::excelToDateTimeObject($value)->format('Y-m-d'))->startOfDay();
            }
            $err[] = "{$label} \"{$value}\" is not a valid date (format mm/dd/yyyy).";
            return null;
        }

        $text = $this->toText($value);
        if (!preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $text, $m)) {
            $err[] = "{$label} \"{$text}\" must use mm/dd/yyyy format.";
            return null;
        }
        if (!checkdate((int) $m[1], (int) $m[2], (int) $m[3]) || (int) $m[3] < 1900) {
            $err[] = "{$label} \"{$text}\" is not a valid date (format mm/dd/yyyy).";
            return null;
        }

        return Carbon::create((int) $m[3], (int) $m[1], (int) $m[2])->startOfDay();
    }

    /**
     * Validasi yang butuh database (dilakukan sekaligus per kumpulan VIN, bukan per baris):
     *  - duplikat VIN + tanggal service di dalam file
     *  - VIN sudah delivery (tblfaktur.status_bast = 'TerBast' di WRS Sales)
     *  - service VIN + tanggal yang sama sudah pernah diupload dealer ini
     * Baris yang gagal dipindah dari $clean ke $errors.
     */
    private function validateAgainstDatabase(array &$clean, array &$errors, string $kdDealer): void
    {
        $fail = function (int $rowNo, string $message) use (&$clean, &$errors) {
            $errors[$rowNo][] = $message;
            unset($clean[$rowNo]);
        };

        // ---- duplikat di dalam file ----
        $seen = [];
        foreach ($clean as $rowNo => $row) {
            $key = $row['no_vin'] . '|' . $row['service_date']->toDateString();
            if (isset($seen[$key])) {
                $fail($rowNo, "Duplicate row: same No Vin and Tanggal Service as row {$seen[$key]}.");
            } else {
                $seen[$key] = $rowNo;
            }
        }

        $vins = array_values(array_unique(array_column($clean, 'no_vin')));
        if (!$vins) {
            return;
        }

        // ---- VIN sudah delivery? ----
        try {
            $delivered = $this->deliveredVins($vins);
        } catch (Throwable $e) {
            report($e);
            throw new InvalidArgumentException('Tidak dapat memeriksa status delivery VIN ke database WRS Sales. Silakan coba beberapa saat lagi.');
        }

        foreach ($clean as $rowNo => $row) {
            if (!isset($delivered[$row['no_vin']])) {
                $fail($rowNo, "VIN {$row['no_vin']} has not been delivered yet (no faktur with BAST status \"" . self::BAST_DELIVERED . '").');
            }
        }

        // ---- service yang sama sudah pernah diupload? ----
        $customers = $this->existingCustomers(array_values(array_unique(array_column($clean, 'no_vin'))), $kdDealer);
        $kdList = $customers->flatten(1)->pluck('kd_customer')->all();
        if (!$kdList) {
            return;
        }

        $dates = array_map(fn ($r) => $r['service_date'], $clean);
        $existing = [];
        foreach (array_chunk($kdList, 500) as $chunk) {
            DB::table('tblkpi')
                ->where('fk_dealer', $kdDealer)
                ->whereIn('fk_customer', $chunk)
                ->whereBetween('tanggal_faktur', [min($dates)->format('Y-m-d 00:00:00'), max($dates)->format('Y-m-d 23:59:59')])
                ->get(['fk_customer', 'tanggal_faktur'])
                ->each(function ($k) use (&$existing) {
                    $existing[$k->fk_customer . '|' . substr((string) $k->tanggal_faktur, 0, 10)] = true;
                });
        }

        foreach ($clean as $rowNo => $row) {
            foreach ($customers->get($row['no_vin'], collect()) as $cust) {
                if (isset($existing[$cust->kd_customer . '|' . $row['service_date']->toDateString()])) {
                    $fail($rowNo, "Service for VIN {$row['no_vin']} on " . $row['service_date']->format('m/d/Y') . ' has already been uploaded.');
                    break;
                }
            }
        }
    }

    /** @return array<string, true> */
    private function deliveredVins(array $vins): array
    {
        $delivered = [];
        foreach (array_chunk($vins, 500) as $chunk) {
            DB::connection($this->wrsConnection())->table('tblfaktur')
                ->whereIn('fk_vin', $chunk)
                ->where('status_bast', self::BAST_DELIVERED)
                ->distinct()
                ->pluck('fk_vin')
                ->each(function ($vin) use (&$delivered) {
                    $delivered[$vin] = true;
                });
        }

        return $delivered;
    }

    /**
     * Customer dealer ini per VIN, terbaru dulu (tanggal_last_service, lalu kd_customer).
     *
     * @return Collection<string, Collection>
     */
    private function existingCustomers(array $vins, string $kdDealer): Collection
    {
        $rows = collect();
        foreach (array_chunk($vins, 500) as $chunk) {
            $rows = $rows->concat(
                DB::table('tblcustomer')
                    ->where('fk_dealer', $kdDealer)
                    ->whereIn('no_vin', $chunk)
                    ->get(['kd_customer', 'no_vin', 'tanggal_last_service'])
            );
        }

        return $rows
            ->sortBy([['tanggal_last_service', 'desc'], ['kd_customer', 'desc']])
            ->groupBy(fn ($c) => strtoupper(trim((string) $c->no_vin)));
    }

    /**
     * Data kendaraan dari tblinventory (WRS Sales), utamakan yang aktif.
     *
     * @return array<string, object>
     */
    private function inventories(array $vins): array
    {
        $result = [];
        foreach (array_chunk($vins, 500) as $chunk) {
            DB::connection($this->wrsConnection())->table('tblinventory')
                ->whereIn('no_vin', $chunk)
                ->orderByDesc('is_active')
                ->get(['no_vin', 'no_mesin', 'fk_model', 'fk_type', 'fk_color', 'tahun_pembuatan'])
                ->each(function ($inv) use (&$result) {
                    $result[$inv->no_vin] ??= $inv;
                });
        }

        return $result;
    }

    /**
     * Tanggal handover per VIN dari tblfaktur lokal (kolom milik After Sales, tidak ada di tblfaktur WRS Sales).
     * VIN dengan beberapa faktur: dipakai tanggal handover terbaru.
     *
     * @return array<string, string> no_vin => 'Y-m-d H:i:s'
     */
    private function handoverDates(array $vins): array
    {
        $result = [];
        foreach (array_chunk($vins, 500) as $chunk) {
            DB::table('tblfaktur')
                ->whereIn('fk_vin', $chunk)
                ->whereNotNull('tanggal_handover')
                ->groupBy('fk_vin')
                ->selectRaw('fk_vin, MAX(tanggal_handover) AS tanggal_handover')
                ->get()
                ->each(function ($f) use (&$result) {
                    $result[$f->fk_vin] = Carbon::parse($f->tanggal_handover)->format('Y-m-d H:i:s');
                });
        }

        return $result;
    }

    private function wrsConnection(): string
    {
        return (string) config('afsel.service_upload.wrs_connection', 'db_wrs_sales');
    }

    // =========================================================================
    // Simpan
    // =========================================================================

    private function save(array $clean, string $kdDealer): array
    {
        $customers = $this->existingCustomers(array_values(array_unique(array_column($clean, 'no_vin'))), $kdDealer)
            ->map(fn ($list) => $list->first());

        // Kelompokkan per VIN; baris dengan tanggal service terbaru jadi acuan data customer
        $byVin = [];
        foreach ($clean as $rowNo => $row) {
            $latest = $byVin[$row['no_vin']] ?? null;
            if (!$latest || $row['service_date']->gt($latest['service_date'])) {
                $byVin[$row['no_vin']] = $row;
            }
        }

        $newVins = array_values(array_filter(array_keys($byVin), fn ($vin) => !$customers->has($vin)));

        $inventories = $newVins ? $this->inventories($newVins) : [];
        $handovers   = $newVins ? $this->handoverDates($newVins) : [];

        // Nomor direserve di transaksi pendek sendiri (lihat DocumentNumberService) sebelum transaksi utama
        $customerIds = $this->numbers->reserve('tblcustomer', count($newVins));
        $kpiIds      = $this->numbers->reserve('tblkpi', count($clean));
        $wpIds       = $this->numbers->reserve('tblwork_planning', count($clean));
        $wpPkIds     = $this->numbers->reserve('tblwork_planning_pk_id', count($clean));
        $logIds      = $this->customerLog->reserve(count($byVin));   // 1 log per customer (insert / update)

        $today = now()->startOfDay();

        return DB::transaction(function () use ($clean, $kdDealer, $customers, $byVin, $newVins, $inventories, $handovers, $customerIds, $kpiIds, $wpIds, $wpPkIds, $logIds, $today) {
            $logAt = now();
            $kdByVin = $customers->map(fn ($c) => $c->kd_customer)->all();

            // ---- insert customer baru ----
            $insertCustomers = [];
            foreach ($newVins as $i => $vin) {
                $row = $byVin[$vin];
                $inv = $inventories[$vin] ?? null;
                $kd  = $customerIds[$i];
                $kdByVin[$vin] = $kd;

                $insertCustomers[] = ['kd_customer' => $kd] + $this->customerData($row) + [
                    'no_vin'               => $vin,
                    'no_mesin'             => $inv ? $this->nullIfEmpty(trim((string) $inv->no_mesin)) : null,
                    'fk_model'             => $inv ? $this->nullIfEmpty(trim((string) $inv->fk_model)) : null,
                    'fk_type'              => $inv ? $this->nullIfEmpty(trim((string) $inv->fk_type)) : null,
                    'fk_color'             => $inv ? $this->nullIfEmpty(trim((string) $inv->fk_color)) : null,
                    'tahun'                => $inv && $inv->tahun_pembuatan !== null ? substr((string) (int) $inv->tahun_pembuatan, 0, 4) : null,
                    // Tanggal handover dari tblfaktur; belum ada → tanggal upload
                    'tanggal_penyerahan'   => $handovers[$vin] ?? $today->format('Y-m-d H:i:s'),
                    'tanggal_last_service' => $row['service_date']->format('Y-m-d H:i:s'),
                    'fk_dealer'            => $kdDealer,
                    'status_customer'      => 'Approved',
                    'fk_user_approval'     => null,
                    'tgl_approval'         => null,
                    'status_approval'      => null,
                    'note_approval'        => null,
                    'nm_dpn_pemakai'       => null,
                    'nm_dpn_customer'      => null,
                    'counter_repurchase'   => null,
                ];
            }

            foreach (array_chunk($insertCustomers, 200) as $chunk) {
                DB::table('tblcustomer')->insert($chunk);
            }

            // ---- log customer baru (snapshot sesudah insert) ----
            $newKds  = array_column($insertCustomers, 'kd_customer');
            $logged  = $this->customerLog->log($newKds, CustomerLogService::MODE_INSERT, array_slice($logIds, 0, count($newKds)), $logAt);

            // ---- customer lama: data customer diperbarui dari Excel ----
            $updatedKds = [];
            $isNew = array_flip($newVins);
            foreach ($byVin as $vin => $row) {
                if (isset($isNew[$vin])) {
                    continue;
                }
                $this->updateExistingCustomer($kdByVin[$vin], $kdDealer, $row);
                $updatedKds[] = $kdByVin[$vin];
            }

            // ---- log customer lama (snapshot sesudah update) ----
            $logged += $this->customerLog->log($updatedKds, CustomerLogService::MODE_UPDATE, array_slice($logIds, count($newKds)), $logAt);

            // ---- insert tblwork_planning + tblkpi (selalu, 1 baris Excel = 1 work planning + 1 kpi) ----
            $workPlannings = [];
            $kpis = [];
            $i = 0;
            foreach ($clean as $row) {
                $kdCustomer      = $kdByVin[$row['no_vin']];
                $workPlannings[] = $this->workPlanningRow($wpIds[$i], $wpPkIds[$i], $kdDealer, $kdCustomer, $row);
                $kpis[]          = $this->kpiRow($kpiIds[$i], $kdDealer, $kdCustomer, $wpIds[$i], $row);
                $i++;
            }
            foreach (array_chunk($workPlannings, 200) as $chunk) {
                DB::table('tblwork_planning')->insert($chunk);
            }
            foreach (array_chunk($kpis, 200) as $chunk) {
                DB::table('tblkpi')->insert($chunk);
            }

            return [
                'kpi_inserted'           => count($kpis),
                'work_planning_inserted' => count($workPlannings),
                'customer_inserted'      => count($insertCustomers),
                'customer_updated'       => count($updatedKds),
                'customer_logged'        => $logged,
            ];
        });
    }

    /** Kolom opsional di Excel: kosong → tidak menimpa data customer yang sudah ada. */
    private const OPTIONAL_CUSTOMER_COLUMNS = ['telephone_1', 'email', 'fk_religion', 'tanggal_lahir'];

    /** Data tblcustomer yang berasal dari Excel (dipakai untuk insert customer baru & update customer lama). */
    private function customerData(array $row): array
    {
        return [
            'nomor_polisi'   => $row['no_polisi'],
            'type_customer'  => $row['pembawa'],
            'nama_customer'  => $row['nama_stnk'],
            'nm_pemakai'     => $row['nama_pemakai'],
            'alamat'         => $row['alamat'],
            'telephone_1'    => $this->nullIfEmpty($row['telp_rumah']),
            'telephone_2'    => $row['no_hp'],
            'email'          => $this->nullIfEmpty($row['email']),
            'fk_religion'    => $row['religion_id'],
            'tanggal_lahir'  => $row['birth_date']?->format('Y-m-d H:i:s'),
            'last_odo_meter' => $row['odometer'],
        ];
    }

    /**
     * Customer lama: semua data dari Excel ditulis ke tblcustomer, HANYA jika Tanggal Service di Excel
     * >= tanggal_last_service (data Excel paling baru) — upload service lama tidak menimpa data yang lebih baru.
     * Kolom opsional yang kosong di Excel tidak mengosongkan data yang ada. tanggal_last_service tidak pernah mundur.
     */
    private function updateExistingCustomer(string $kdCustomer, string $kdDealer, array $row): void
    {
        $date = $row['service_date']->format('Y-m-d H:i:s');

        $set = [];
        $bindings = [];
        foreach ($this->customerData($row) as $column => $value) {
            if ($value === null && in_array($column, self::OPTIONAL_CUSTOMER_COLUMNS, true)) {
                continue;
            }
            $set[] = "{$column} = CASE WHEN tanggal_last_service IS NULL OR tanggal_last_service <= ? THEN ? ELSE {$column} END";
            array_push($bindings, $date, $value);
        }

        // Harus paling akhir: MySQL menjalankan SET dari kiri ke kanan, jadi kondisi di atas masih membaca tanggal_last_service lama
        $set[] = 'tanggal_last_service = GREATEST(COALESCE(tanggal_last_service, ?), ?)';
        array_push($bindings, $date, $date, $kdCustomer, $kdDealer);

        DB::update('UPDATE tblcustomer SET ' . implode(', ', $set) . ' WHERE kd_customer = ? AND fk_dealer = ?', $bindings);
    }

    /**
     * tblwork_planning untuk service yang diupload (servis sudah selesai): tanggal-tanggal = Tanggal Service,
     * SA & teknisi dari Nama SA / Nama Teknisi (afsel_dealer_personel.dealer_personel_id).
     */
    private function workPlanningRow(string $kdWp, string $pkId, string $kdDealer, string $kdCustomer, array $row): array
    {
        $date = $row['service_date']->format('Y-m-d H:i:s');

        return [
            'kd_work_planning'                   => $kdWp,
            'fk_booking'                         => null,
            'fk_dealer'                          => $kdDealer,
            'tgl_work_planning'                  => $date,
            'stall_ke'                           => null,
            'fk_customer'                        => $kdCustomer,
            'no_repair_order'                    => $row['no_ro'],
            'customer_request'                   => $row['customer_request'],
            'tgl_datang'                         => $date,
            'jam_datang'                         => $row['service_date']->format('H:i:s'),
            'perkiraan_waktu_selesai'            => $date,
            // Belum ada master grup teknisi di WRS Connect → diisi teknisi yang sama (kolom wajib)
            'fk_group_technician'                => $row['technician_id'],
            'fk_technician'                      => $row['technician_id'],
            'fk_service_advisor'                 => $row['sa_id'],
            'status_1'                           => self::WORK_PLANNING_STATUS_1,
            'status_2'                           => null,
            'ketersediaan_part'                  => null,
            'konfirmasi_waktu_tiba'              => null,
            'status'                             => self::WORK_PLANNING_STATUS,
            'pk_id'                              => $pkId,
            'tgl_datang_sebenarnya'              => $date,
            'perkiraan_waktu_selesai_sebenarnya' => null,
            'fk_technician_2'                    => null,
            'total_kerja'                        => 0,
            'tgl_mulai_process'                  => null,
            'is_backdate_work_planning'          => 0,
            'tgl_pending'                        => null,
        ];
    }

    /**
     * Kode Service → [fk_category_1_vehicles, fk_category_2_vehicles], mengikuti sync_msi.service_map
     * (pemetaan yang sama dipakai Sync MSI untuk reason_for_service). Tidak terdaftar → OTHER (DEFAULT_CATEGORY).
     *
     * @return array{0: string, 1: ?string}
     */
    private function serviceCategories(string $kode): array
    {
        foreach (config('sync_msi.service_map', []) as $m) {
            if ((string) $m['code'] === $kode) {
                return [(string) $m['cat1'], ($m['cat2'] ?? '') === '' ? null : (string) $m['cat2']];
            }
        }

        return [self::DEFAULT_CATEGORY, self::DEFAULT_CATEGORY];
    }

    private function kpiRow(string $kdKpi, string $kdDealer, string $kdCustomer, string $kdWorkPlanning, array $row): array
    {
        [$cat1, $cat2] = $this->serviceCategories($row['kode_service']);

        $data = [
            'kd_kpi'                 => $kdKpi,
            'fk_work_planning'       => $kdWorkPlanning,
            'fk_dealer'              => $kdDealer,
            'fk_customer'            => $kdCustomer,
            'fk_category_1_vehicles' => $cat1,
            'fk_category_2_vehicles' => $cat2,
            'washing'                => null,
            'customer_request'       => $row['customer_request'],
            'tanggal_faktur'         => $row['service_date']->format('Y-m-d H:i:s'),
            'counter_follow_up'      => 0,
            'counter_reminder'       => 0,
            'odo_reminder'           => 0,
            'is_backdate_kpi'        => 0,
            'service_type'           => $row['kode_service'],
            'uploaded_to_msi'        => 0,
        ];

        // Kolom lain di tblkpi diisi NULL (sesuai mapping upload service)
        foreach (self::KPI_NULL_COLUMNS as $column) {
            $data[$column] = null;
        }

        return $data;
    }

    private function nullIfEmpty(?string $value): ?string
    {
        return ($value === null || $value === '') ? null : $value;
    }

    private const KPI_NULL_COLUMNS = [
        'labor_sales', 'part_sales', 'fk_engine_oli', 'qty_engine_oli', 'total_engine_oli', 'fk_transmisi_oli',
        'qty_transmisi_oli', 'total_transmisi_oli', 'qty_tire_sales', 'hrg_tire_sales', 'qty_battery_sales',
        'hrg_battery_sales', 'aksesoris', 'chemical_sales', 'other_sales', 'srt', 'tgl_selesai_aktual',
        'labor_sales_2', 'part_sales_2', 'fk_engine_oli_2', 'qty_engine_oli_2', 'total_engine_oli_2', 'fk_transmisi_oli_2',
        'qty_transmisi_oli_2', 'total_transmisi_oli_2', 'qty_tire_sales_2', 'hrg_tire_sales_2', 'qty_battery_sales_2',
        'hrg_battery_sales_2', 'aksesoris_2', 'chemical_sales_2', 'other_sales_2', 'srt_2', 'tgl_selesai_aktual_2',
        'labor_sales_3', 'part_sales_3', 'fk_engine_oli_3', 'qty_engine_oli_3', 'total_engine_oli_3', 'fk_transmisi_oli_3',
        'qty_transmisi_oli_3', 'total_transmisi_oli_3', 'qty_tire_sales_3', 'hrg_tire_sales_3', 'qty_battery_sales_3',
        'hrg_battery_sales_3', 'aksesoris_3', 'chemical_sales_3', 'other_sales_3', 'srt_3', 'tgl_selesai_aktual_3',
        'pro_active_customer_contact', 'fixed_appointments_before_customer_visit', 'interactive_reception',
        'fixed_repair_price_before_service', 'part_pre_picking_before_scheduled_service', 'all_parts_available_from_warehouse',
        'invoice_ready_before_vehicles_return', 'explanation_of_work_done_on_vehicle', 'follow_up_call_within_5_days',
        'repeated_repair', 'tgl_follow_up', 'status_konfirmasi_1_follow_up', 'return_job_booking', 'fk_booking',
        'status_konfirmasi_2_follow_up', 'catatan_follow_up', 'tgl_reminder', 'status_konfirmasi_reminder',
        'fk_booking_reminder', 'alasan_1_reminder', 'alasan_2_reminder', 'catatan_reminder', 'reschedule_reminder',
        'tgl_akan_di_follow_dari', 'tgl_akan_di_follow_sampai', 'tgl_akan_di_reminder', 'no_polisi_terakhir',
        'catatan_service',
        'pertanyaan_1', 'pertanyaan_2', 'pertanyaan_3', 'pertanyaan_4', 'pertanyaan_5',
        'pertanyaan_6', 'pertanyaan_7', 'pertanyaan_8', 'pertanyaan_9', 'pertanyaan_10',
        'jawaban_1', 'jawaban_2', 'jawaban_3', 'jawaban_4', 'jawaban_5',
        'jawaban_6', 'jawaban_7', 'jawaban_8', 'jawaban_9', 'jawaban_10',
        'jenis_jawaban_1', 'jenis_jawaban_2', 'jenis_jawaban_3', 'jenis_jawaban_4', 'jenis_jawaban_5',
        'jenis_jawaban_6', 'jenis_jawaban_7', 'jenis_jawaban_8', 'jenis_jawaban_9', 'jenis_jawaban_10',
        'status_update_nomor_tlp',
    ];

    // =========================================================================
    // Template
    // =========================================================================

    public function buildTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Data');
        $lastRow = 1000;

        $col = 1;
        $letters = [];
        foreach (self::COLUMNS as $key => $label) {
            $letter = Coordinate::stringFromColumnIndex($col++);
            $letters[$key] = $letter;
            $sheet->setCellValue("{$letter}1", $label);
            $sheet->getColumnDimension($letter)->setWidth(in_array($key, ['customer_request', 'alamat'], true) ? 45 : 20);
        }

        $lastCol = end($letters);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B2027']],
        ]);
        foreach (['tanggal_lahir', 'telp_rumah', 'email', 'agama'] as $optional) {
            $sheet->getStyle($letters[$optional] . '1')->getFill()->getStartColor()->setRGB('6B7280');
        }
        $sheet->freezePane('A2');

        foreach (['tanggal_service', 'tanggal_lahir'] as $key) {
            $sheet->getStyle("{$letters[$key]}2:{$letters[$key]}{$lastRow}")->getNumberFormat()->setFormatCode('mm/dd/yyyy');
        }
        foreach (['no_polisi', 'no_vin', 'no_ro', 'telp_rumah', 'no_hp'] as $key) {
            $sheet->getStyle("{$letters[$key]}2:{$letters[$key]}{$lastRow}")->getNumberFormat()->setFormatCode('@');
        }

        $religions = DB::table('ms_religion')->where('is_active', true)->orderBy('religion')->pluck('religion')->all();
        $lists = [
            'kode_service' => self::SERVICE_CODES,
            'pembawa'      => self::VEHICLE_BRINGERS,
            'agama'        => $religions,
        ];
        foreach ($lists as $key => $values) {
            $validation = new DataValidation();
            $validation->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank($key === 'agama')
                ->setShowDropDown(true)
                ->setShowErrorMessage(true)
                ->setErrorTitle('Input tidak valid')
                ->setError('Pilih salah satu: ' . implode(', ', $values))
                ->setFormula1('"' . implode(',', $values) . '"');
            $sheet->setDataValidation("{$letters[$key]}2:{$letters[$key]}{$lastRow}", $validation);
        }

        // ---- sheet petunjuk ----
        $guide = $spreadsheet->createSheet()->setTitle('Petunjuk');
        $guide->fromArray([
            ['Kolom', 'Wajib', 'Aturan'],
            ['No Polisi', 'Ya', 'Huruf besar & angka, tanpa spasi/karakter lain. Contoh: B1234ABC'],
            ['No Vin', 'Ya', 'Huruf besar & angka, tanpa spasi/karakter lain. VIN harus sudah delivery (status BAST "TerBast").'],
            ['Tanggal Service', 'Ya', 'Format mm/dd/yyyy. Tidak boleh lebih dari tanggal hari ini.'],
            ['Oddometer', 'Ya', 'Angka tanpa pemisah ribuan.'],
            ['No Repair Order', 'Ya', 'Teks.'],
            ['Service Permintaan Pelanggan', 'Ya', 'Teks.'],
            ['Kode Service', 'Ya', implode(', ', self::SERVICE_CODES) . ' (huruf besar).'],
            ['Nama SA', 'Ya', 'Nama personel aktif level Service Advisor di dealer Anda. Huruf besar/kecil harus sama persis.'],
            ['Nama Teknisi', 'Ya', 'Nama personel aktif level Technician di dealer Anda. Huruf besar/kecil harus sama persis.'],
            ['Yang Membawa Kendaraan', 'Ya', 'Owner atau Driver.'],
            ['Nama Pemakai', 'Ya', 'Nama orang yang memakai kendaraan. Teks, maks. 110 karakter.'],
            ['Nama STNK', 'Ya', 'Nama pemilik sesuai STNK (disimpan sebagai nama customer). Teks, maks. 110 karakter.'],
            ['Alamat', 'Ya', 'Teks.'],
            ['Telp Rumah', 'Tidak', 'Teks.'],
            ['No HP', 'Ya', 'Teks (format kolom Text agar angka 0 di depan tidak hilang).'],
            ['Email', 'Tidak', 'Teks.'],
            ['Agama', 'Tidak', implode(', ', $religions) . '.'],
            ['Tanggal Lahir', 'Tidak', 'Format mm/dd/yyyy.'],
            ['', '', ''],
            ['Catatan', '', 'Jika ada 1 baris saja yang error, seluruh file TIDAK disimpan. Perbaiki/keluarkan baris yang error lalu upload ulang.'],
            ['', '', 'VIN yang sudah terdaftar di dealer Anda: data customer diperbarui sesuai Excel jika Tanggal Service adalah yang terbaru. Kolom opsional yang kosong tidak menghapus data yang ada.'],
        ]);
        $guide->getStyle('A1:C1')->getFont()->setBold(true);
        $guide->getColumnDimension('A')->setWidth(30);
        $guide->getColumnDimension('B')->setWidth(8);
        $guide->getColumnDimension('C')->setWidth(110);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}

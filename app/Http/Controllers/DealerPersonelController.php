<?php

namespace App\Http\Controllers;

use App\Exports\DealerPersonelExport;
use App\Models\Afsel\DealerPersonel;
use App\Services\Afsel\DealerPersonelPhotoService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

/**
 * Dealer Personel After Sales dari sisi Dealer (afsel_dealer_personel).
 *  - Hanya personel milik dealer user login (session user.kd_dealer).
 *  - Tanpa hapus: personel yang sudah tidak bekerja cukup dinonaktifkan.
 *  - Personel baru selalu disimpan Nonaktif; aktivasi hanya oleh ATPM (dealer hanya bisa menonaktifkan).
 *  - Nama & No KTP tidak boleh sama dengan personel yang sedang Aktif (semua dealer).
 */
class DealerPersonelController extends Controller
{
    private const TABLE = 'afsel_dealer_personel';

    public function index()
    {
        return view('aftersales.dealer.personel.index', [
            'hasDealer'   => $this->dealer() !== null,
            'levels'      => DB::table('afsel_dealer_level_personel')->orderBy('dealer_level_personel')->get(),
            'canCreate'   => afsel_can('personel.create'),
            'canEdit'     => afsel_can('personel.edit'),
            'canStatus'   => afsel_can('personel.status'),
            'canExport'   => afsel_can('personel.export'),
            'photoMaxKb'  => DealerPersonelPhotoService::UPLOAD_MAX_KB,
        ]);
    }

    public function datatable(Request $request)
    {
        return DataTables::of($this->buildQuery($request))
            ->filterColumn('name', fn ($q, $kw) => $q->where(fn ($w) => $w->where('p.name', 'like', "%{$kw}%")->orWhere('p.nm_panggilan', 'like', "%{$kw}%")))
            ->filterColumn('nik', fn ($q, $kw) => $q->where(fn ($w) => $w->where('p.nik', 'like', "%{$kw}%")->orWhere('p.no_ktp', 'like', "%{$kw}%")))
            ->filterColumn('dealer_level_personel', fn ($q, $kw) => $q->where('l.dealer_level_personel', 'like', "%{$kw}%"))
            ->editColumn('tgl_masuk', fn ($r) => $r->tgl_masuk ? date('d M Y', strtotime($r->tgl_masuk)) : '-')
            ->editColumn('tgl_keluar', fn ($r) => $r->tgl_keluar ? date('d M Y', strtotime($r->tgl_keluar)) : null)
            // Semua kolom di-escape di browser (fungsi esc() di view)
            ->escapeColumns([])
            ->make(true);
    }

    /** Download Excel (.xlsx) sesuai filter & pencarian yang sedang dipakai di halaman. */
    public function export(Request $request)
    {
        $query = $this->buildQuery($request)
            ->addSelect(['p.alamat'])
            ->orderByDesc('p.is_active')
            ->orderBy('p.name');

        $fileName = 'Dealer-Personel_' . ($this->dealer() ?? 'dealer') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new DealerPersonelExport($query), $fileName);
    }

    /**
     * Personel dealer user login + filter level, status & pencarian (q).
     * Dipakai bersama oleh datatable dan export agar hasilnya sama.
     */
    private function buildQuery(Request $request): Builder
    {
        $query = DB::table(self::TABLE . ' as p')
            ->leftJoin('afsel_dealer_level_personel as l', 'l.dealer_level_personel_id', '=', 'p.dealer_level_personel_id')
            ->where('p.fk_dealer', $this->dealer() ?? '')
            ->select([
                'p.dealer_personel_id', 'p.nik', 'p.name', 'p.nm_panggilan', 'p.no_ktp',
                'p.tgl_masuk', 'p.tgl_keluar', 'p.alasan_keluar', 'p.is_active', 'p.is_blacklist', 'p.is_blacklist_atpm',
                'l.dealer_level_personel', 'l.jenis',
            ]);

        if ($level = $request->input('level')) {
            $query->where('p.dealer_level_personel_id', $level);
        }
        switch ($request->input('status')) {
            case 'active':    $query->where('p.is_active', true); break;
            case 'inactive':  $query->where('p.is_active', false); break;
            case 'blacklist': $query->where(fn ($q) => $q->where('p.is_blacklist', true)->orWhere('p.is_blacklist_atpm', true)); break;
        }

        // Export: kata kunci dari kotak "Cari" tabel (datatable memakai pencarian bawaan DataTables)
        if (($kw = trim((string) $request->input('q'))) !== '') {
            $query->where(fn ($w) => $w->where('p.name', 'like', "%{$kw}%")->orWhere('p.nm_panggilan', 'like', "%{$kw}%")
                ->orWhere('p.nik', 'like', "%{$kw}%")->orWhere('p.no_ktp', 'like', "%{$kw}%")
                ->orWhere('l.dealer_level_personel', 'like', "%{$kw}%"));
        }

        return $query;
    }

    /** Detail personel (modal detail & form ubah). */
    public function show(string $id, DealerPersonelPhotoService $photos): JsonResponse
    {
        $p = DB::table(self::TABLE . ' as p')
            ->leftJoin('afsel_dealer_level_personel as l', 'l.dealer_level_personel_id', '=', 'p.dealer_level_personel_id')
            ->where('p.dealer_personel_id', $id)
            ->where('p.fk_dealer', $this->dealer() ?? '')
            ->select('p.*', 'l.dealer_level_personel', 'l.jenis')
            ->first();

        if (!$p) {
            return $this->fail('Personel tidak ditemukan.', 404);
        }

        $masuk  = $p->tgl_masuk ? Carbon::parse($p->tgl_masuk) : null;
        $keluar = $p->tgl_keluar ? Carbon::parse($p->tgl_keluar) : null;

        $masaKerja = null;
        if ($masuk) {
            $diff = $masuk->diff($keluar ?? now());
            $masaKerja = trim(($diff->y ? $diff->y . ' tahun ' : '') . ($diff->m ? $diff->m . ' bulan' : '')) ?: 'Kurang dari 1 bulan';
        }

        return response()->json(['status' => true, 'data' => [
            'id'                       => $p->dealer_personel_id,
            'name'                     => $p->name,
            'nm_panggilan'             => $p->nm_panggilan,
            'nik'                      => $p->nik,
            'no_ktp'                   => $p->no_ktp,
            'alamat'                   => $p->alamat,
            'dealer_level_personel_id' => $p->dealer_level_personel_id,
            'level'                    => $p->dealer_level_personel,
            'jenis'                    => $p->jenis,
            // Nilai mentah (Y-m-d) untuk form, versi teks untuk tampilan
            'tgl_masuk'                => $masuk?->format('Y-m-d'),
            'tgl_keluar'               => $keluar?->format('Y-m-d'),
            'tgl_masuk_text'           => $masuk?->locale('id')->translatedFormat('d F Y'),
            'tgl_keluar_text'          => $keluar?->locale('id')->translatedFormat('d F Y'),
            'masa_kerja'               => $masaKerja,
            'alasan_keluar'            => $p->alasan_keluar,
            'is_active'                => (bool) $p->is_active,
            'is_blacklist'             => (bool) $p->is_blacklist,
            'is_blacklist_atpm'        => (bool) $p->is_blacklist_atpm,
            'photo_url'                => $photos->available($p)
                ? route('aftersales.dealer.personel.dealer-personel.photo', ['id' => $p->dealer_personel_id, 'v' => $photos->version($p)])
                : null,
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        $kdDealer = $this->dealer();
        if (!$kdDealer) {
            return $this->fail('Akun Anda belum terhubung ke dealer.', 403);
        }

        $validator = $this->validator($request);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();

        if ($message = $this->activeDuplicate($data['name'], $data['no_ktp'])) {
            return $this->fail($message, 422);
        }

        $photos = app(DealerPersonelPhotoService::class);

        $id = DB::transaction(function () use ($data, $kdDealer, $request, $photos) {
            $id = DealerPersonel::nextId();

            DB::table(self::TABLE)->insert($this->identity($data) + [
                'dealer_personel_id' => $id,
                'fk_dealer'          => $kdDealer,
                'picture'            => '',
                'picture_ext'        => '',
                // Personel baru selalu Nonaktif
                'is_active'          => false,
                'is_blacklist'       => false,
                'is_blacklist_atpm'  => false,
                'tgl_keluar'         => null,
                'alasan_keluar'      => null,
            ]);

            if ($request->hasFile('photo')) {
                DB::table(self::TABLE)->where('dealer_personel_id', $id)->update($photos->store($id, $request->file('photo')));
            }

            return $id;
        });

        Log::info('[DealerPersonel] tambah oleh dealer', ['dealer_personel_id' => $id, 'fk_dealer' => $kdDealer, 'by' => (string) session('user.id')]);

        return response()->json(['status' => true, 'message' => 'Personel berhasil ditambahkan dengan status Nonaktif.', 'id' => $id]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $before = DB::table(self::TABLE)->where('dealer_personel_id', $id)->where('fk_dealer', $this->dealer() ?? '')->first();
        if (!$before) {
            return $this->fail('Personel tidak ditemukan.', 404);
        }

        $canEdit   = afsel_can('personel.edit');
        $canStatus = afsel_can('personel.status');

        $validator = $this->validator($request, $before, $canEdit, $canStatus);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        $data    = $validator->validated();
        $changes = $canEdit ? $this->identity($data) : [];

        if ($canStatus) {
            $active = (bool) $data['is_active'];
            $changes += [
                'is_active'     => $active,
                'is_blacklist'  => (bool) $data['is_blacklist'],
                // Personel aktif tidak punya data keluar
                'tgl_keluar'    => $active ? null : ($data['tgl_keluar'] ?? null),
                'alasan_keluar' => $active ? null : $this->clean($data['alasan_keluar'] ?? null),
            ];

            // Aktivasi hanya oleh ATPM: dealer cuma bisa menonaktifkan
            if ($active && !$before->is_active) {
                return $this->fail('Personel nonaktif hanya bisa diaktifkan oleh ATPM.', 422);
            }

            if ($active && ($changes['is_blacklist'] || $before->is_blacklist_atpm)) {
                return $this->fail('Personel yang masuk daftar blacklist harus dinonaktifkan.', 422);
            }
        }

        $name     = $changes['name'] ?? $before->name;
        $ktp      = $changes['no_ktp'] ?? $before->no_ktp;
        $isActive = $changes['is_active'] ?? (bool) $before->is_active;
        $identityChanged = $name !== $before->name || $ktp !== $before->no_ktp;

        // Cek bentrok dengan personel aktif lain saat personel ini akan aktif atau nama/KTP-nya diubah
        if (($isActive || $identityChanged) && ($message = $this->activeDuplicate($name, $ktp, $id))) {
            return $this->fail($message, 422);
        }

        if ($canEdit && $request->hasFile('photo')) {
            $changes += app(DealerPersonelPhotoService::class)->store($id, $request->file('photo'));
        }

        DB::table(self::TABLE)->where('dealer_personel_id', $id)->update($changes);

        Log::info('[DealerPersonel] ubah oleh dealer', [
            'dealer_personel_id' => $id,
            'by'                 => (string) session('user.id'),
            'before'             => collect((array) $before)->only(array_keys($changes))->all(),
            'after'              => $changes,
        ]);

        return response()->json(['status' => true, 'message' => 'Data personel berhasil diperbarui.']);
    }

    /** Foto personel (upload dealer / salinan WRS), hanya personel dealer sendiri. */
    public function photo(string $id, DealerPersonelPhotoService $photos)
    {
        $p = DB::table(self::TABLE)->where('dealer_personel_id', $id)->where('fk_dealer', $this->dealer() ?? '')
            ->first(['dealer_personel_id', 'picture', 'picture_ext']);

        $photo = $p ? $photos->get($p) : null;
        abort_unless($photo, 404);

        [$content, $mime] = $photo;

        return response($content, 200, [
            'Content-Type'           => $mime,
            'Cache-Control'          => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // ─────────────────────────────────────────────────────────────

    private function dealer(): ?string
    {
        $kd = session('user.kd_dealer');

        return $kd !== null && $kd !== '' ? (string) $kd : null;
    }

    /**
     * @param object|null $before data lama (null = tambah baru)
     */
    private function validator(Request $request, ?object $before = null, bool $identity = true, bool $status = false)
    {
        $rules = [];

        if ($identity) {
            // Level nonaktif tidak bisa dipilih, kecuali memang level personel ini sekarang
            $levelRule = Rule::exists('afsel_dealer_level_personel', 'dealer_level_personel_id')
                ->where(fn ($q) => $q->where('is_active', true)
                    ->when($before, fn ($w) => $w->orWhere('dealer_level_personel_id', $before->dealer_level_personel_id)));

            // Data lama dari WRS bisa berformat lain: format 16 digit hanya dipaksa bila No KTP diubah
            $ktpUnchanged = $before && trim((string) $request->input('no_ktp')) === (string) $before->no_ktp;

            $rules += [
                'name'                     => ['required', 'string', 'max:150'],
                'nm_panggilan'             => ['nullable', 'string', 'max:100'],
                'dealer_level_personel_id' => ['required', 'string', $levelRule],
                'nik'                      => ['required', 'string', 'max:50'],
                'no_ktp'                   => $ktpUnchanged ? ['required', 'string', 'max:255'] : ['required', 'digits:16'],
                'alamat'                   => ['nullable', 'string', 'max:1000'],
                'tgl_masuk'                => ['required', 'date', 'before_or_equal:today'],
                'photo'                    => ['nullable', 'file', 'max:' . DealerPersonelPhotoService::UPLOAD_MAX_KB,
                    'mimes:' . implode(',', DealerPersonelPhotoService::UPLOAD_EXTENSIONS),
                    'extensions:' . implode(',', DealerPersonelPhotoService::UPLOAD_EXTENSIONS)],
            ];
        }

        if ($status) {
            $rules += [
                'is_active'     => ['required', 'boolean'],
                'is_blacklist'  => ['required', 'boolean'],
                'tgl_keluar'    => ['nullable', 'date', 'before_or_equal:today'],
                'alasan_keluar' => ['nullable', 'string', 'max:1000'],
            ];
        }

        $validator = Validator::make($request->all(), $rules, [
            'name.required'                     => 'Nama wajib diisi.',
            'dealer_level_personel_id.required' => 'Level wajib dipilih.',
            'dealer_level_personel_id.exists'   => 'Level tidak ditemukan atau sudah nonaktif.',
            'nik.required'                      => 'NIK wajib diisi.',
            'no_ktp.required'                   => 'No KTP wajib diisi.',
            'no_ktp.digits'                     => 'No KTP harus 16 digit angka.',
            'tgl_masuk.required'                => 'Tanggal masuk wajib diisi.',
            'tgl_masuk.before_or_equal'         => 'Tanggal masuk tidak boleh lebih dari hari ini.',
            'tgl_keluar.before_or_equal'        => 'Tanggal keluar tidak boleh lebih dari hari ini.',
            'is_active.required'                => 'Status Active wajib diisi.',
            'is_blacklist.required'             => 'Status Black List wajib diisi.',
            'photo.max'                         => 'Ukuran foto maksimal ' . DealerPersonelPhotoService::UPLOAD_MAX_KB . ' KB.',
            'photo.mimes'                       => 'Foto harus berformat .jpg, .jpeg atau .png.',
            'photo.extensions'                  => 'Foto harus berformat .jpg, .jpeg atau .png.',
            'photo.uploaded'                    => 'Foto gagal di-upload. Ukuran maksimal ' . DealerPersonelPhotoService::UPLOAD_MAX_KB . ' KB.',
        ]);

        // Tanggal keluar tidak boleh sebelum tanggal masuk
        $validator->after(function ($v) use ($request, $before) {
            $masuk  = $request->input('tgl_masuk') ?? ($before?->tgl_masuk ? substr($before->tgl_masuk, 0, 10) : null);
            $keluar = $request->input('tgl_keluar');
            if ($masuk && $keluar && strtotime($keluar) && strtotime($masuk) && strtotime($keluar) < strtotime($masuk)) {
                $v->errors()->add('tgl_keluar', 'Tanggal keluar tidak boleh sebelum tanggal masuk.');
            }
        });

        return $validator;
    }

    /** Kolom identitas yang diisi dari form. */
    private function identity(array $data): array
    {
        return [
            'name'                     => $this->clean($data['name']),
            'nm_panggilan'             => $this->clean($data['nm_panggilan'] ?? null),
            'dealer_level_personel_id' => $data['dealer_level_personel_id'],
            'nik'                      => $this->clean($data['nik']),
            'no_ktp'                   => $this->clean($data['no_ktp']),
            'alamat'                   => $this->clean($data['alamat'] ?? null) ?? '',
            'tgl_masuk'                => $data['tgl_masuk'],
        ];
    }

    /** Trim + rapikan spasi ganda; string kosong → null. */
    private function clean(?string $value): ?string
    {
        $value = $value === null ? '' : trim(preg_replace('/\s+/u', ' ', $value));

        return $value === '' ? null : $value;
    }

    private function activeDuplicate(string $name, ?string $ktp, ?string $exceptId = null): ?string
    {
        return DealerPersonel::activeDuplicate($this->clean($name), $this->clean($ktp), $exceptId, $this->dealer());
    }

    private function fail(string $message, int $code = 422, array $errors = []): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message, 'errors' => $errors], $code);
    }
}

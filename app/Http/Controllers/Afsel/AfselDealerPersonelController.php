<?php

namespace App\Http\Controllers\Afsel;

use App\Http\Controllers\Controller;
use App\Models\Afsel\DealerPersonel;
use App\Services\Afsel\DealerPersonelPhotoService;
use App\Services\Afsel\DealerPersonelSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

/**
 * Dealer Personel After Sales (afsel_dealer_personel).
 * Index (server-side datatable) + sync dari WRS After Sales (Postgres).
 */
class AfselDealerPersonelController extends Controller
{
    public function __construct(private DealerPersonelSyncService $syncService) {}

    public function index()
    {
        $stats = $this->stats();

        return view('aftersales.atpm.page_personel.dealer_personel', [
            'stats'    => $stats,
            // Pilihan filter dealer: hanya dealer aktif
            'dealers'  => DB::table('tbldealer')->where('is_active', true)->orderBy('nm_dealer')->get(['kd_dealer', 'nm_dealer']),
            'levels'   => DB::table('afsel_dealer_level_personel')->orderBy('dealer_level_personel')->get(),
            'lastSync' => $this->syncService->lastSync(),
            'canSync'  => afsel_can('dealer-personel.sync'),
            'canEdit'  => afsel_can('dealer-personel.edit'),
        ]);
    }

    /**
     * Update dari sisi ATPM: hanya status Active & Black List EMI (is_blacklist_atpm).
     * Aktivasi personel hanya dilakukan di sini (dealer hanya bisa menonaktifkan).
     * Data keluar lain (tanggal keluar, blacklist dealer, alasan keluar) milik dealer → tidak diubah di sini.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_active'         => ['required', 'boolean'],
            'is_blacklist_atpm' => ['required', 'boolean'],
        ], [
            'is_active.required'         => 'Status Active wajib diisi.',
            'is_blacklist_atpm.required' => 'Status Black List EMI wajib diisi.',
            'is_active.boolean'          => 'Status Active harus Ya atau Tidak.',
            'is_blacklist_atpm.boolean'  => 'Status Black List EMI harus Ya atau Tidak.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $before = DB::table('afsel_dealer_personel')->where('dealer_personel_id', $id)->first(['is_active', 'is_blacklist_atpm', 'name', 'no_ktp']);
        if (!$before) {
            return response()->json(['status' => false, 'message' => 'Personel tidak ditemukan.'], 404);
        }

        // Aktivasi (hanya dari ATPM): Nama & No KTP tidak boleh sama dengan personel lain yang sedang aktif
        if ($request->boolean('is_active') && !$before->is_active
            && ($message = DealerPersonel::activeDuplicate($before->name, $before->no_ktp, $id))) {
            return response()->json(['status' => false, 'message' => $message], 422);
        }

        $data = [
            'is_active'         => (bool) $request->boolean('is_active'),
            'is_blacklist_atpm' => (bool) $request->boolean('is_blacklist_atpm'),
        ];

        DB::table('afsel_dealer_personel')->where('dealer_personel_id', $id)->update($data);

        // Tabel tidak punya kolom audit → jejak perubahan dicatat di log
        Log::info('[DealerPersonel] update oleh ATPM', [
            'dealer_personel_id' => $id,
            'by'                 => (string) session('user.id'),
            'before'             => ['is_active' => (bool) $before->is_active, 'is_blacklist_atpm' => (bool) $before->is_blacklist_atpm],
            'after'              => $data,
        ]);

        return response()->json(['status' => true, 'message' => 'Data personel berhasil diperbarui.', 'stats' => $this->stats()]);
    }

    private function stats(): object
    {
        return DB::table('afsel_dealer_personel')->selectRaw('
                count(*) as total,
                coalesce(sum(is_active = 1), 0) as active,
                coalesce(sum(is_blacklist = 1 or is_blacklist_atpm = 1), 0) as blacklist,
                count(distinct fk_dealer) as dealers
            ')->first();
    }

    public function datatable(Request $request)
    {
        $query = DB::table('afsel_dealer_personel as p')
            ->leftJoin('afsel_dealer_level_personel as l', 'l.dealer_level_personel_id', '=', 'p.dealer_level_personel_id')
            ->leftJoin('tbldealer as d', 'd.kd_dealer', '=', 'p.fk_dealer')
            ->select([
                'p.dealer_personel_id', 'p.nik', 'p.name', 'p.nm_panggilan', 'p.no_ktp', 'p.fk_dealer',
                'p.tgl_masuk', 'p.tgl_keluar', 'p.alasan_keluar', 'p.is_active', 'p.is_blacklist', 'p.is_blacklist_atpm',
                'l.dealer_level_personel', 'l.jenis', 'd.nm_dealer',
            ]);

        if ($dealer = $request->input('fk_dealer')) {
            $query->where('p.fk_dealer', $dealer);
        }
        if ($level = $request->input('level')) {
            $query->where('p.dealer_level_personel_id', $level);
        }
        switch ($request->input('status')) {
            case 'active':    $query->where('p.is_active', true); break;
            case 'inactive':  $query->where('p.is_active', false); break;
            case 'blacklist': $query->where(fn ($q) => $q->where('p.is_blacklist', true)->orWhere('p.is_blacklist_atpm', true)); break;
        }

        return DataTables::of($query)
            ->filterColumn('name', fn ($q, $kw) => $q->where(fn ($w) => $w->where('p.name', 'like', "%{$kw}%")->orWhere('p.nm_panggilan', 'like', "%{$kw}%")))
            ->filterColumn('nik', fn ($q, $kw) => $q->where(fn ($w) => $w->where('p.nik', 'like', "%{$kw}%")->orWhere('p.no_ktp', 'like', "%{$kw}%")))
            ->filterColumn('nm_dealer', fn ($q, $kw) => $q->where(fn ($w) => $w->where('d.nm_dealer', 'like', "%{$kw}%")->orWhere('p.fk_dealer', 'like', "%{$kw}%")))
            ->filterColumn('dealer_level_personel', fn ($q, $kw) => $q->where('l.dealer_level_personel', 'like', "%{$kw}%"))
            ->editColumn('tgl_masuk', fn ($r) => $r->tgl_masuk ? date('d M Y', strtotime($r->tgl_masuk)) : '-')
            ->editColumn('tgl_keluar', fn ($r) => $r->tgl_keluar ? date('d M Y', strtotime($r->tgl_keluar)) : null)
            // Teks dikirim apa adanya; semua kolom di-escape di browser (fungsi esc() di view).
            // Tanpa ini teks ter-escape dua kali, mis. RO'UP tampil sebagai RO&#039;UP.
            ->escapeColumns([])
            ->make(true);
    }

    /** Detail personel untuk modal. */
    public function show(string $id, DealerPersonelPhotoService $photos): JsonResponse
    {
        $p = DB::table('afsel_dealer_personel as p')
            ->leftJoin('afsel_dealer_level_personel as l', 'l.dealer_level_personel_id', '=', 'p.dealer_level_personel_id')
            ->leftJoin('tbldealer as d', 'd.kd_dealer', '=', 'p.fk_dealer')
            ->where('p.dealer_personel_id', $id)
            ->select('p.*', 'l.dealer_level_personel', 'l.jenis', 'd.nm_dealer')
            ->first();

        if (!$p) {
            return response()->json(['status' => false, 'message' => 'Personel tidak ditemukan.'], 404);
        }

        $masuk  = $p->tgl_masuk ? Carbon::parse($p->tgl_masuk) : null;
        $keluar = $p->tgl_keluar ? Carbon::parse($p->tgl_keluar) : null;

        $masaKerja = null;
        if ($masuk) {
            $diff = $masuk->diff($keluar ?? now());
            $masaKerja = trim(($diff->y ? $diff->y . ' tahun ' : '') . ($diff->m ? $diff->m . ' bulan' : '')) ?: 'Kurang dari 1 bulan';
        }

        return response()->json(['status' => true, 'data' => [
            'id'                => $p->dealer_personel_id,
            'name'              => $p->name,
            'nm_panggilan'      => $p->nm_panggilan,
            'nik'               => $p->nik,
            'no_ktp'            => $p->no_ktp,
            'alamat'            => $p->alamat,
            'level'             => $p->dealer_level_personel,
            'jenis'             => $p->jenis,
            'fk_dealer'         => $p->fk_dealer,
            'nm_dealer'         => $p->nm_dealer,
            'tgl_masuk'         => $masuk?->locale('id')->translatedFormat('d F Y'),
            'tgl_keluar'        => $keluar?->locale('id')->translatedFormat('d F Y'),
            'masa_kerja'        => $masaKerja,
            'alasan_keluar'     => $p->alasan_keluar,
            'is_active'         => (bool) $p->is_active,
            'is_blacklist'      => (bool) $p->is_blacklist,
            'is_blacklist_atpm' => (bool) $p->is_blacklist_atpm,
            'picture'           => $p->picture,
            'photo_url'         => $photos->available($p) ? route('aftersales.atpm.dealer_personel.photo', $p->dealer_personel_id) : null,
        ]]);
    }

    /** Foto personel (proxy dari server WRS, hanya untuk user yang berhak). */
    public function photo(string $id, DealerPersonelPhotoService $photos)
    {
        $p = DB::table('afsel_dealer_personel')->where('dealer_personel_id', $id)
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

    public function sync(): JsonResponse
    {
        // Cegah sync dobel (klik dua kali / dua admin bersamaan)
        $lock = Cache::lock('afsel_dealer_personel_sync', 600);
        if (!$lock->get()) {
            return response()->json(['status' => false, 'message' => 'Sync sedang berjalan. Tunggu hingga selesai.'], 409);
        }

        try {
            $result = $this->syncService->sync((string) session('user.id'));
        } catch (\Throwable $e) {
            // Pesan error bisa membawa byte non UTF-8 dari data sumber
            $message = mb_scrub($e->getMessage(), 'UTF-8');
            Log::error('[DealerPersonelSync] gagal: ' . $message);

            return response()->json(['status' => false, 'message' => 'Sync gagal: ' . \Illuminate\Support\Str::limit($message, 300)], 500);
        } finally {
            $lock->release();
        }

        return response()->json([
            'status'  => true,
            'message' => "Sync selesai: {$result['inserted']} ditambahkan, {$result['updated']} diperbarui.",
            'result'  => $result,
        ]);
    }
}

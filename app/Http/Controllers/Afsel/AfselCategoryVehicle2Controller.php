<?php

namespace App\Http\Controllers\Afsel;

use App\Http\Controllers\Controller;
use App\Models\Afsel\CategoryVehicle;
use App\Models\Afsel\CategoryVehicle2;
use App\Services\Afsel\DocumentNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * General Setting > Category Service > Cat Service Vehicle 2 (tblcategory_2_vehicles).
 * Tiap data terhubung ke Cat Service Vehicle 1 (fk_category_vehicles); field Jenis di form
 * (Customer Service Sales / Internal Service Sales) memfilter pilihan Cat Service Vehicle 1.
 * Kode baru diambil dari Document Numbering (table_name = tblcategory_2_vehicles).
 */
class AfselCategoryVehicle2Controller extends Controller
{
    private const DOC_TABLE = 'tblcategory_2_vehicles';

    public function __construct(private DocumentNumberService $numbers)
    {
    }

    public function index()
    {
        $categories = CategoryVehicle2::query()
            ->leftJoin('tblcategory_vehicles as c1', 'c1.kd_category_vehicles', '=', 'tblcategory_2_vehicles.fk_category_vehicles')
            ->select('tblcategory_2_vehicles.*', 'c1.nm_category_vehicles', 'c1.jenis', 'c1.is_active as parent_is_active')
            ->orderByDesc('tblcategory_2_vehicles.is_active')
            ->orderBy('c1.jenis')
            ->orderBy('c1.nm_category_vehicles')
            ->orderBy('tblcategory_2_vehicles.nm_category_2_vehicles')
            ->get();

        $parents = CategoryVehicle::query()
            ->orderBy('jenis')
            ->orderBy('nm_category_vehicles')
            ->get(['kd_category_vehicles', 'nm_category_vehicles', 'jenis', 'is_active']);

        return view('aftersales.atpm.page_general_setting.category_vehicle_2', [
            'categories' => $categories,
            'parents'    => $parents,
            'jenisList'  => CategoryVehicle::JENIS,
            'canCreate'  => afsel_can('category-vehicle-2.create'),
            'canEdit'    => afsel_can('category-vehicle-2.edit'),
            'canDelete'  => afsel_can('category-vehicle-2.delete'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = $this->validator($request);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $kode = $this->numbers->next(self::DOC_TABLE);

        CategoryVehicle2::create([
            'kd_category_2_vehicles' => $kode,
            'nm_category_2_vehicles' => $data['nm_category_2_vehicles'],
            'fk_category_vehicles'   => $data['fk_category_vehicles'],
            'is_active'              => $data['is_active'] ?? true,
        ]);

        return $this->ok('Cat Service Vehicle 2 berhasil ditambahkan.', ['id' => $kode]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $category = CategoryVehicle2::find($id);
        if (!$category) {
            return $this->fail('Cat Service Vehicle 2 tidak ditemukan.', [], 404);
        }

        $validator = $this->validator($request, $id);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), $validator->errors()->toArray());
        }

        $data = $validator->validated();

        $category->update([
            'nm_category_2_vehicles' => $data['nm_category_2_vehicles'],
            'fk_category_vehicles'   => $data['fk_category_vehicles'],
            'is_active'              => $data['is_active'] ?? $category->is_active,
        ]);

        return $this->ok('Cat Service Vehicle 2 berhasil diubah.');
    }

    public function destroy(string $id): JsonResponse
    {
        $category = CategoryVehicle2::find($id);
        if (!$category) {
            return $this->fail('Cat Service Vehicle 2 tidak ditemukan.', [], 404);
        }

        if (DB::table('tblkpi')->where('fk_category_2_vehicles', $id)->exists()) {
            return $this->fail('Tidak bisa dihapus karena sudah dipakai pada data service. Nonaktifkan saja kategori ini.');
        }

        $category->delete();

        return $this->ok('Cat Service Vehicle 2 berhasil dihapus.');
    }

    private function validator(Request $request, ?string $ignoreId = null)
    {
        $request->merge(['nm_category_2_vehicles' => trim((string) $request->input('nm_category_2_vehicles'))]);
        $jenis = $request->input('jenis');

        return Validator::make($request->all(), [
            'jenis'                => ['required', Rule::in(CategoryVehicle::JENIS)],
            'fk_category_vehicles' => [
                'required', 'string',
                // Cat Service Vehicle 1 harus ada & sesuai Jenis yang dipilih
                Rule::exists('tblcategory_vehicles', 'kd_category_vehicles')->where('jenis', $jenis),
            ],
            'nm_category_2_vehicles' => [
                'required', 'string', 'max:255',
                // Nama boleh sama asalkan beda Cat Service Vehicle 1 (mis. "Customer Paid")
                Rule::unique('tblcategory_2_vehicles', 'nm_category_2_vehicles')
                    ->where('fk_category_vehicles', (string) $request->input('fk_category_vehicles'))
                    ->ignore($ignoreId, 'kd_category_2_vehicles'),
            ],
            'is_active' => ['boolean'],
        ], [
            'jenis.required'                  => 'Jenis wajib dipilih.',
            'jenis.in'                        => 'Jenis harus Customer Service Sales atau Internal Service Sales.',
            'fk_category_vehicles.required'   => 'Cat Service Vehicle 1 wajib dipilih.',
            'fk_category_vehicles.exists'     => 'Cat Service Vehicle 1 tidak ditemukan atau tidak sesuai dengan Jenis.',
            'nm_category_2_vehicles.required' => 'Nama category wajib diisi.',
            'nm_category_2_vehicles.unique'   => 'Nama category sudah ada pada Cat Service Vehicle 1 yang dipilih.',
            'nm_category_2_vehicles.max'      => 'Nama category maksimal 255 karakter.',
        ]);
    }

    private function ok(string $message, array $extra = []): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $message, 'errors' => ''] + $extra);
    }

    private function fail(string $message, array $errors = [], int $code = 422): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message, 'errors' => $errors], $code);
    }
}

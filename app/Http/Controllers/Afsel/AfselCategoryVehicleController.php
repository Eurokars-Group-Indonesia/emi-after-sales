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
 * General Setting > Category Service > Cat Service Vehicle 1 (tblcategory_vehicles).
 * Kode baru diambil dari Document Numbering (table_name = tblcategory_vehicles).
 * Hapus hanya untuk kategori yang belum dipakai (Cat Service Vehicle 2 / data service tblkpi);
 * selain itu cukup dinonaktifkan.
 */
class AfselCategoryVehicleController extends Controller
{
    private const DOC_TABLE = 'tblcategory_vehicles';

    public function __construct(private DocumentNumberService $numbers)
    {
    }

    public function index()
    {
        $categories = CategoryVehicle::query()
            ->withCount('children')
            ->orderByDesc('is_active')
            ->orderBy('jenis')
            ->orderBy('nm_category_vehicles')
            ->get();

        return view('aftersales.atpm.page_general_setting.category_vehicle_1', [
            'categories' => $categories,
            'jenisList'  => CategoryVehicle::JENIS,
            'canCreate'  => afsel_can('category-vehicle-1.create'),
            'canEdit'    => afsel_can('category-vehicle-1.edit'),
            'canDelete'  => afsel_can('category-vehicle-1.delete'),
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

        CategoryVehicle::create([
            'kd_category_vehicles' => $kode,
            'nm_category_vehicles' => $data['nm_category_vehicles'],
            'jenis'                => $data['jenis'],
            'is_active'            => $data['is_active'] ?? true,
        ]);

        return $this->ok('Cat Service Vehicle 1 berhasil ditambahkan.', ['id' => $kode]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $category = CategoryVehicle::find($id);
        if (!$category) {
            return $this->fail('Cat Service Vehicle 1 tidak ditemukan.', [], 404);
        }

        $validator = $this->validator($request, $id);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), $validator->errors()->toArray());
        }

        $data = $validator->validated();

        $category->update([
            'nm_category_vehicles' => $data['nm_category_vehicles'],
            'jenis'                => $data['jenis'],
            'is_active'            => $data['is_active'] ?? $category->is_active,
        ]);

        return $this->ok('Cat Service Vehicle 1 berhasil diubah.');
    }

    public function destroy(string $id): JsonResponse
    {
        $category = CategoryVehicle::find($id);
        if (!$category) {
            return $this->fail('Cat Service Vehicle 1 tidak ditemukan.', [], 404);
        }

        if (CategoryVehicle2::where('fk_category_vehicles', $id)->exists()) {
            return $this->fail('Tidak bisa dihapus karena masih dipakai oleh Cat Service Vehicle 2. Nonaktifkan saja kategori ini.');
        }
        if (DB::table('tblkpi')->where('fk_category_1_vehicles', $id)->exists()) {
            return $this->fail('Tidak bisa dihapus karena sudah dipakai pada data service. Nonaktifkan saja kategori ini.');
        }

        $category->delete();

        return $this->ok('Cat Service Vehicle 1 berhasil dihapus.');
    }

    private function validator(Request $request, ?string $ignoreId = null)
    {
        $request->merge(['nm_category_vehicles' => trim((string) $request->input('nm_category_vehicles'))]);

        return Validator::make($request->all(), [
            'nm_category_vehicles' => [
                'required', 'string', 'max:255',
                Rule::unique('tblcategory_vehicles', 'nm_category_vehicles')->ignore($ignoreId, 'kd_category_vehicles'),
            ],
            'jenis'     => ['required', Rule::in(CategoryVehicle::JENIS)],
            'is_active' => ['boolean'],
        ], [
            'nm_category_vehicles.required' => 'Nama category wajib diisi.',
            'nm_category_vehicles.unique'   => 'Nama category sudah ada.',
            'nm_category_vehicles.max'      => 'Nama category maksimal 255 karakter.',
            'jenis.required'                => 'Jenis wajib dipilih.',
            'jenis.in'                      => 'Jenis harus Customer Service Sales atau Internal Service Sales.',
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

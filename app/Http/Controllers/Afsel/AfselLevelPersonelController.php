<?php

namespace App\Http\Controllers\Afsel;

use App\Http\Controllers\Controller;
use App\Models\Afsel\DealerLevelPersonel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Master Level Personel After Sales (afsel_dealer_level_personel).
 * Tanpa hapus permanen: level yang tidak dipakai dinonaktifkan (is_active = false).
 */
class AfselLevelPersonelController extends Controller
{
    public function index()
    {
        $levels = DealerLevelPersonel::query()
            ->orderByDesc('is_active')
            ->orderBy('dealer_level_personel')
            ->get();

        return view('aftersales.atpm.page_personel.level_personel', [
            'levels'    => $levels,
            'jenisList' => DealerLevelPersonel::JENIS,
            'canCreate' => afsel_can('level-personel.create'),
            'canEdit'   => afsel_can('level-personel.edit'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = $this->validator($request);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), $validator->errors()->toArray());
        }

        $data = $validator->validated();

        $id = DB::transaction(function () use ($data) {
            $id = DealerLevelPersonel::nextId();

            DealerLevelPersonel::create([
                'dealer_level_personel_id' => $id,
                'dealer_level_personel'    => $data['dealer_level_personel'],
                'jenis'                    => $data['jenis'] ?? null,
                'is_active'                => $data['is_active'] ?? true,
            ]);

            return $id;
        });

        return $this->ok('Level personel berhasil ditambahkan.', ['id' => $id]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $level = DealerLevelPersonel::find($id);
        if (!$level) {
            return $this->fail('Level personel tidak ditemukan.', [], 404);
        }

        $validator = $this->validator($request, $id);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), $validator->errors()->toArray());
        }

        $data = $validator->validated();

        $level->update([
            'dealer_level_personel' => $data['dealer_level_personel'],
            'jenis'                 => $data['jenis'] ?? null,
            'is_active'             => $data['is_active'] ?? $level->is_active,
        ]);

        return $this->ok('Level personel berhasil diubah.');
    }

    private function validator(Request $request, ?string $ignoreId = null)
    {
        $request->merge(['dealer_level_personel' => trim((string) $request->input('dealer_level_personel'))]);

        return Validator::make($request->all(), [
            'dealer_level_personel' => [
                'required', 'string', 'max:255',
                Rule::unique('afsel_dealer_level_personel', 'dealer_level_personel')
                    ->ignore($ignoreId, 'dealer_level_personel_id'),
            ],
            'jenis'     => ['nullable', Rule::in(DealerLevelPersonel::JENIS)],
            'is_active' => ['boolean'],
        ], [
            'dealer_level_personel.required' => 'Nama level personel wajib diisi.',
            'dealer_level_personel.unique'   => 'Nama level personel sudah ada.',
            'dealer_level_personel.max'      => 'Nama level personel maksimal 255 karakter.',
            'jenis.in'                       => 'Jenis harus Direct atau Indirect.',
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

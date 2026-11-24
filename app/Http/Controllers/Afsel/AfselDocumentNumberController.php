<?php

namespace App\Http\Controllers\Afsel;

use App\Http\Controllers\Controller;
use App\Services\Afsel\DocumentNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Document Numbering (ms_afsel_atpm_document_number): counter nomor dokumen yyyymmdd + 20 digit per table_name.
 */
class AfselDocumentNumberController extends Controller
{
    public function __construct(private DocumentNumberService $numbers)
    {
    }

    public function index()
    {
        $rows = DB::table(DocumentNumberService::TABLE)->orderBy('table_name')->get()
            ->map(function ($row) {
                $row->next_number = $this->numbers->preview($row);
                $row->is_today    = $row->last_date && substr((string) $row->last_date, 0, 10) === now()->toDateString();
                return $row;
            });

        return view('aftersales.atpm.page_document_number.index', [
            'rows'      => $rows,
            'canManage' => afsel_can('document-number.manage'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['table_name' => trim((string) $request->input('table_name'))]);

        $validator = Validator::make($request->all(), [
            'table_name'  => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique(DocumentNumberService::TABLE, 'table_name')],
            'last_number' => ['required', 'integer', 'min:0'],
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), $validator->errors()->toArray());
        }

        DB::table(DocumentNumberService::TABLE)->insert([
            'table_name'   => $request->input('table_name'),
            'last_number'  => (int) $request->input('last_number'),
            'last_date'    => now()->toDateString(),
            'created_by'   => (string) session('user.id'),
            'date_created' => now(),
        ]);

        return $this->ok('Document number berhasil ditambahkan.');
    }

    /**
     * Ubah nomor urut terakhir hari ini. Dikunci (FOR UPDATE) agar tidak bertabrakan dengan proses upload yang sedang jalan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'last_number' => ['required', 'integer', 'min:0'],
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), $validator->errors()->toArray());
        }

        $found = DB::transaction(function () use ($id, $request) {
            $row = DB::table(DocumentNumberService::TABLE)->where('DocNumId', $id)->lockForUpdate()->first();
            if (!$row) {
                return false;
            }

            DB::table(DocumentNumberService::TABLE)->where('DocNumId', $id)->update([
                'last_number'  => (int) $request->input('last_number'),
                'last_date'    => now()->toDateString(),
                'updated_by'   => (string) session('user.id'),
                'date_updated' => now(),
            ]);

            return true;
        });

        return $found
            ? $this->ok('Nomor urut berhasil diubah.')
            : $this->fail('Data document number tidak ditemukan.', [], 404);
    }

    private function messages(): array
    {
        return [
            'table_name.required'  => 'Table name wajib diisi.',
            'table_name.regex'     => 'Table name hanya boleh huruf, angka dan underscore.',
            'table_name.unique'    => 'Table name sudah terdaftar.',
            'last_number.required' => 'Nomor urut terakhir wajib diisi.',
            'last_number.integer'  => 'Nomor urut terakhir harus angka.',
            'last_number.min'      => 'Nomor urut terakhir minimal 0.',
        ];
    }

    private function ok(string $message): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $message, 'errors' => '']);
    }

    private function fail(string $message, array $errors = [], int $code = 422): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message, 'errors' => $errors], $code);
    }
}

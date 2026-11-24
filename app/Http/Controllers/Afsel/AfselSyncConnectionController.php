<?php

namespace App\Http\Controllers\Afsel;

use App\Http\Controllers\Controller;
use App\Services\SyncWrs\SyncConnectionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Setting koneksi database sumber sync (ms_sync_db_connection). Tidak ada hapus: koneksi yang tidak dipakai di-non-aktifkan.
 */
class AfselSyncConnectionController extends Controller
{
    public function __construct(private SyncConnectionManager $connections)
    {
    }

    public function index()
    {
        $usedBy = collect(config('sync_wrs.jobs', []))->groupBy('connection')->map(fn ($jobs) => $jobs->pluck('label')->all());
        $msi    = (string) config('sync_msi.connection', 'MSI');
        $usedBy[$msi] = array_merge($usedBy[$msi] ?? [], [config('sync_msi.label', 'Sync MSI')]);

        $rows = DB::table(SyncConnectionManager::TABLE)->orderBy('name')
            ->get(['id', 'name', 'driver', 'hostname', 'username', 'database', 'port', 'remarks', 'is_active', 'password',
                'created_by', 'date_created', 'updated_by', 'date_updated'])
            ->map(function ($row) use ($usedBy) {
                $row->has_password = !empty($row->password);
                unset($row->password);
                $row->used_by = $usedBy[$row->name] ?? [];

                return $row;
            });

        return view('aftersales.atpm.page_sync_wrs.connection', [
            'rows'      => $rows,
            'drivers'   => SyncConnectionManager::DRIVERS,
            'canManage' => afsel_can('sync-connection.manage'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['name' => strtoupper(trim((string) $request->input('name')))]);

        $validator = Validator::make($request->all(), $this->rules() + [
            'name'     => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9_]+$/', Rule::unique(SyncConnectionManager::TABLE, 'name')],
            'password' => ['nullable', 'string', 'max:255'],
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        DB::table(SyncConnectionManager::TABLE)->insert($this->payload($request) + [
            'name'         => $request->input('name'),
            'password'     => $this->connections->encrypt($request->input('password')),
            'created_by'   => (string) session('user.id'),
            'date_created' => now(),
        ]);

        return $this->ok('Koneksi berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules() + [
            'password' => ['nullable', 'string', 'max:255'],
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        if (!DB::table(SyncConnectionManager::TABLE)->where('id', $id)->exists()) {
            return $this->fail('Koneksi tidak ditemukan.', 404);
        }

        $data = $this->payload($request) + [
            'updated_by'   => (string) session('user.id'),
            'date_updated' => now(),
        ];
        // Password kosong = tetap memakai password lama
        if ($request->filled('password')) {
            $data['password'] = $this->connections->encrypt($request->input('password'));
        }

        DB::table(SyncConnectionManager::TABLE)->where('id', $id)->update($data);

        return $this->ok('Koneksi berhasil diubah.');
    }

    /** Test koneksi dari isian form; password kosong + id → pakai password tersimpan. */
    public function test(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $password = $request->input('password');
        if (($password === null || $password === '') && $request->filled('id')) {
            try {
                $password = $this->connections->decrypt(
                    DB::table(SyncConnectionManager::TABLE)->where('id', (int) $request->input('id'))->value('password')
                );
            } catch (Throwable $e) {
                return $this->fail($e->getMessage());
            }
        }

        $result = $this->connections->test(
            $request->input('driver'), $request->input('hostname'), (string) $request->input('port'),
            $request->input('database'), $request->input('username'), $password
        );

        return response()->json(['status' => $result['ok'], 'message' => $result['message'], 'errors' => '']);
    }

    private function rules(): array
    {
        return [
            'driver'    => ['required', Rule::in(array_keys(SyncConnectionManager::DRIVERS))],
            'hostname'  => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'max:255'],
            'database'  => ['required', 'string', 'max:255'],
            'port'      => ['required', 'integer', 'between:1,65535'],
            'remarks'   => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function payload(Request $request): array
    {
        return [
            'driver'    => $request->input('driver'),
            'hostname'  => trim((string) $request->input('hostname')),
            'username'  => trim((string) $request->input('username')),
            'database'  => trim((string) $request->input('database')),
            'port'      => (string) (int) $request->input('port'),
            'remarks'   => $request->input('remarks'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function messages(): array
    {
        return [
            'name.required'     => 'Nama koneksi wajib diisi.',
            'name.regex'        => 'Nama koneksi hanya huruf besar, angka dan underscore (mis. WRS_SALES).',
            'name.unique'       => 'Nama koneksi sudah terdaftar.',
            'driver.in'         => 'Driver tidak dikenal.',
            'hostname.required' => 'Hostname wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'database.required' => 'Database wajib diisi.',
            'port.required'     => 'Port wajib diisi.',
            'port.integer'      => 'Port harus angka.',
            'port.between'      => 'Port tidak valid.',
        ];
    }

    private function ok(string $message): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $message, 'errors' => '']);
    }

    private function fail(string $message, int $code = 422): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message, 'errors' => ''], $code);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\Afsel\ServiceUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * Upload Service (Dealer): upload Excel data service → tblcustomer + tblkpi dealer user login.
 * Hasil upload tampil di menu Data Service (DealerServiceDataController).
 */
class DealerServiceUploadController
{
    public function __construct(private ServiceUploadService $service)
    {
    }

    public function index()
    {
        return view('aftersales.dealer.service_upload.index', [
            'serviceCodes' => ServiceUploadService::SERVICE_CODES,
            'maxRows'      => (int) config('afsel.service_upload.max_rows', 2000),
            'maxFileMb'    => round(config('afsel.service_upload.max_file_kb', 10240) / 1024, 1),
        ]);
    }

    public function template()
    {
        $spreadsheet = $this->service->buildTemplate();

        // Ditulis ke file sementara dulu (bukan php://output) agar isi file .xlsx utuh
        // dan tidak tercampur output buffer / header lain.
        $path = tempnam(sys_get_temp_dir(), 'afsel_tpl_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, 'Template-Upload-Service.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function upload(Request $request): JsonResponse
    {
        $kdDealer = trim((string) session('user.kd_dealer'));
        if ($kdDealer === '') {
            return $this->fail('Kode dealer user tidak ditemukan. Silakan login ulang.', 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'extensions:xlsx', 'mimes:xlsx', 'max:' . (int) config('afsel.service_upload.max_file_kb', 10240)],
        ], [
            'file.required'   => 'Pilih file Excel yang akan diupload.',
            'file.extensions' => 'File harus berformat .xlsx. File .xls tidak didukung, simpan ulang (Save As) sebagai Excel Workbook (.xlsx).',
            'file.mimes'      => 'File harus berformat .xlsx. File .xls tidak didukung, simpan ulang (Save As) sebagai Excel Workbook (.xlsx).',
            'file.max'      => 'Ukuran file terlalu besar.',
            'file.uploaded' => 'File gagal diupload. Periksa ukuran file.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        @set_time_limit(600);
        @ini_set('memory_limit', '1024M');

        // Satu upload per dealer pada satu waktu: mencegah customer ganda saat 2 user dealer yang sama upload bersamaan
        $lock = Cache::lock('afsel:service-upload:' . $kdDealer, 600);
        if (!$lock->get()) {
            return $this->fail('Upload service lain untuk dealer Anda sedang diproses. Silakan coba lagi beberapa saat.', 423);
        }

        try {
            $result = $this->service->process($request->file('file')->getRealPath(), $kdDealer);

            Log::info('Afsel service upload', [
                'dealer' => $kdDealer,
                'user'   => session('user.id'),
                'file'   => $request->file('file')->getClientOriginalName(),
                'status' => $result['status'],
                'total'  => $result['total'],
                'errors' => count($result['errors']),
            ] + $result['summary']);

            return response()->json($result, $result['status'] ? 200 : 422);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->fail('Terjadi kesalahan saat memproses upload. Tidak ada data yang disimpan. Silakan coba lagi.', 500);
        } finally {
            $lock->release();
        }
    }

    private function fail(string $message, int $code = 422): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'total'   => 0,
            'errors'  => [],
            'summary' => [],
        ], $code);
    }
}

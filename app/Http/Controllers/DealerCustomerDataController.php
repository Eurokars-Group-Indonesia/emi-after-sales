<?php

namespace App\Http\Controllers;

use App\Exports\DealerCustomerDataExport;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class DealerCustomerDataController
{
    /**
     * Kolom yang ikut dicari oleh field "Search" (pencarian semua kolom).
     */
    protected array $searchColumns = [
        'nomor_polisi', 'type_customer', 'nama_customer', 'telephone_1', 'telephone_2',
        'email', 'no_vin', 'no_mesin', 'last_odo_meter', 'nm_pemakai', 'alamat',
    ];

    public function index()
    {
        $kdDealer = session('user.kd_dealer');

        $data['dealer'] = $kdDealer
            ? DB::table('tbldealer')->where('kd_dealer', $kdDealer)->first(['kd_dealer', 'nm_dealer'])
            : null;

        $data['stats'] = $kdDealer
            ? DB::table('tblcustomer')
                ->where('fk_dealer', $kdDealer)
                ->selectRaw('COUNT(*) as total, MAX(tanggal_last_service) as last_service')
                ->first()
            : (object) ['total' => 0, 'last_service' => null];

        return view('aftersales.dealer.customer_data.index', $data);
    }

    public function datatable(Request $request)
    {
        return DataTables::query($this->buildQuery($request))
            ->editColumn('tanggal_last_service', fn ($row) => $row->tanggal_last_service
                ? date('m-d-Y', strtotime($row->tanggal_last_service))
                : null)
            ->editColumn('last_odo_meter', fn ($row) => $row->last_odo_meter !== null
                ? number_format((float) $row->last_odo_meter, 0, ',', '.')
                : null)
            ->make(true);
    }

    /**
     * Download Excel (.xlsx) sesuai filter yang sedang dipakai di halaman.
     */
    public function export(Request $request)
    {
        // Data customer satu dealer bisa puluhan ribu baris
        @set_time_limit(600);
        @ini_set('memory_limit', '1024M');

        $query = $this->buildQuery($request)
            ->orderBy('tanggal_last_service', 'desc')
            ->orderBy('kd_customer');

        $fileName = 'Customer-Data_' . (session('user.kd_dealer') ?: 'dealer') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new DealerCustomerDataExport($query), $fileName);
    }

    /**
     * Query customer milik dealer user yang login + filter tanggal service & search.
     * Dipakai bersama oleh datatable dan export agar hasilnya selalu sama.
     */
    private function buildQuery(Request $request): Builder
    {
        $kdDealer = session('user.kd_dealer');
        $fromDate = $this->parseDate($request->input('srcFromDate'));
        $toDate   = $this->parseDate($request->input('srcToDate'));
        $srcText  = trim((string) $request->input('srcText', ''));

        $query = DB::table('tblcustomer')
            ->select(
                'kd_customer',
                'nomor_polisi',
                'type_customer',
                'nama_customer',
                'telephone_1',
                'telephone_2',
                'email',
                'no_vin',
                'no_mesin',
                'tanggal_last_service',
                'last_odo_meter',
                'nm_pemakai',
                'alamat'
            );

        // Hanya customer milik dealer user yang login; tanpa kd_dealer tidak tampil apa pun.
        if (empty($kdDealer)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->where('fk_dealer', $kdDealer);
        }

        if ($fromDate) {
            $query->where('tanggal_last_service', '>=', $fromDate . ' 00:00:00');
        }

        if ($toDate) {
            $query->where('tanggal_last_service', '<=', $toDate . ' 23:59:59');
        }

        if ($srcText !== '') {
            $like = '%' . addcslashes($srcText, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                foreach ($this->searchColumns as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
        }

        return $query;
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $time = strtotime($value);

        return $time ? date('Y-m-d', $time) : null;
    }
}

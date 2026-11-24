<?php

namespace App\Http\Controllers;

use App\Exports\DealerServiceDataExport;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class DealerServiceDataController
{
    public function index()
    {
        return view('aftersales.dealer.service_data.index');
    }

    public function datatable(Request $request)
    {
        // Jumlah data dihitung tanpa LEFT JOIN model/kategori: kodenya unik (tidak menggandakan baris)
        // dan search hanya memakai kolom tblkpi/tblcustomer. Jauh lebih cepat dari COUNT bawaan yajra.
        $srcText = trim((string) $request->input('srcText', ''));
        $count   = $this->applyDealerAndDateFilter(DB::table('tblkpi'), $request);

        if ($srcText === '') {
            $count->whereExists(fn ($q) => $q->selectRaw('1')->from('tblcustomer')->whereColumn('tblcustomer.kd_customer', 'tblkpi.fk_customer'));
        } else {
            $count->join($this->customerTable(), 'tblcustomer.kd_customer', '=', 'tblkpi.fk_customer');
            $this->applySearch($count, $srcText);
        }

        $count = $count->count();

        $query = $this->buildQuery($request);

        // Tanpa klik sort kolom: urutan default (service hari ini paling atas).
        // Jika user klik header kolom, yajra yang mengurutkan sesuai kolom tsb.
        if (empty($request->input('order'))) {
            $this->applyDefaultOrder($query);
        }

        return DataTables::query($query)
            ->setTotalRecords($count)
            ->setFilteredRecords($count)
            ->editColumn('tanggal_faktur', fn ($row) => $row->tanggal_faktur
                ? date('m-d-Y', strtotime($row->tanggal_faktur))
                : null)
            ->make(true);
    }

    /**
     * Download Excel (.xlsx) sesuai filter yang sedang dipakai di halaman.
     */
    public function export(Request $request)
    {
        // Data service satu dealer bisa puluhan ribu baris
        @set_time_limit(600);
        @ini_set('memory_limit', '1024M');

        $query = $this->applyDefaultOrder($this->buildQuery($request));

        $fileName = 'Service-Data_' . (session('user.kd_dealer') ?: 'dealer') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new DealerServiceDataExport($query), $fileName);
    }

    /**
     * Urutan default: tanggal service terbaru s/d hari ini paling atas (data upload terbaru dulu bila tanggalnya sama).
     * Data lama dengan tanggal service di masa depan (salah input, mis. 2043) ditaruh paling bawah
     * agar tidak menutupi service hari ini.
     */
    private function applyDefaultOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw('tblkpi.tanggal_faktur > ? ASC', [now()->endOfDay()->format('Y-m-d H:i:s')])
            ->orderBy('tblkpi.tanggal_faktur', 'desc')
            ->orderBy('tblkpi.kd_kpi', 'desc');
    }

    /**
     * Data service (tblkpi) yang dikerjakan dealer user login + filter tanggal service & search.
     * Model & kategori memakai LEFT JOIN agar data service tanpa model/kategori tetap tampil.
     * Dipakai bersama oleh datatable dan export agar hasilnya selalu sama.
     */
    private function buildQuery(Request $request): Builder
    {
        $srcText = trim((string) $request->input('srcText', ''));

        $query = DB::table('tblkpi')
            // Index tanggal saja membuat MySQL memindai mundur seluruh tblkpi lalu menyaring dealer (lambat);
            // lewat index fk_dealer jauh lebih cepat.
            ->ignoreIndex('tblkpi_tanggal_faktur_index')
            ->join($this->customerTable(), 'tblcustomer.kd_customer', '=', 'tblkpi.fk_customer')
            ->leftJoin('tblmodel', 'tblcustomer.fk_model', '=', 'tblmodel.kd_model')
            ->leftJoin('tblcategory_vehicles', 'tblkpi.fk_category_1_vehicles', '=', 'tblcategory_vehicles.kd_category_vehicles')
            ->leftJoin('tblcategory_2_vehicles', 'tblkpi.fk_category_2_vehicles', '=', 'tblcategory_2_vehicles.kd_category_2_vehicles')
            ->select(
                'tblkpi.kd_kpi',
                'tblcustomer.nomor_polisi',
                'tblcustomer.no_vin',
                'tblmodel.nm_model',
                'tblkpi.tanggal_faktur',
                'tblcategory_vehicles.nm_category_vehicles',
                'tblcategory_2_vehicles.nm_category_2_vehicles',
                'tblkpi.customer_request'
            );

        $this->applyDealerAndDateFilter($query, $request);

        if ($srcText !== '') {
            $this->applySearch($query, $srcText);
        }

        return $query;
    }

    /**
     * Search semua kolom. Nama model & kategori dicari dulu di tabel master (kecil) lalu dipakai
     * sebagai daftar kode, sehingga MySQL bisa menyaring tblkpi/tblcustomer tanpa LIKE lewat LEFT JOIN
     * (search jadi ±3x lebih cepat).
     */
    private function applySearch(Builder $query, string $srcText): void
    {
        $like = '%' . addcslashes($srcText, '%_\\') . '%';

        $modelCodes = DB::table('tblmodel')->where('nm_model', 'like', $like)->pluck('kd_model')->all();
        $cat1Codes  = DB::table('tblcategory_vehicles')->where('nm_category_vehicles', 'like', $like)->pluck('kd_category_vehicles')->all();
        $cat2Codes  = DB::table('tblcategory_2_vehicles')->where('nm_category_2_vehicles', 'like', $like)->pluck('kd_category_2_vehicles')->all();

        $query->where(function ($q) use ($like, $modelCodes, $cat1Codes, $cat2Codes) {
            $q->where('tblcustomer.nomor_polisi', 'like', $like)
                ->orWhere('tblcustomer.no_vin', 'like', $like)
                ->orWhere('tblkpi.customer_request', 'like', $like);

            if ($modelCodes) {
                $q->orWhereIn('tblcustomer.fk_model', $modelCodes);
            }
            if ($cat1Codes) {
                $q->orWhereIn('tblkpi.fk_category_1_vehicles', $cat1Codes);
            }
            if ($cat2Codes) {
                $q->orWhereIn('tblkpi.fk_category_2_vehicles', $cat2Codes);
            }
        });
    }

    /**
     * Hanya service di dealer user yang login (tanpa kd_dealer tidak tampil apa pun) + rentang tanggal service.
     */
    private function applyDealerAndDateFilter(Builder $query, Request $request): Builder
    {
        $kdDealer = session('user.kd_dealer');
        $fromDate = $this->parseDate($request->input('srcFromDate'));
        $toDate   = $this->parseDate($request->input('srcToDate'));

        if (empty($kdDealer)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->where('tblkpi.fk_dealer', $kdDealer);
        }

        if ($fromDate) {
            $query->where('tblkpi.tanggal_faktur', '>=', $fromDate . ' 00:00:00');
        }

        if ($toDate) {
            $query->where('tblkpi.tanggal_faktur', '<=', $toDate . ' 23:59:59');
        }

        return $query;
    }

    /**
     * tblcustomer + FORCE INDEX covering (kd_customer, nomor_polisi, no_vin, fk_model) bila index-nya ada.
     * MySQL tidak memilih index ini sendiri, padahal join & search jadi ±2,5x lebih cepat
     * karena cukup membaca index tanpa membaca baris tblcustomer yang lebar.
     * Dicek dulu karena FORCE INDEX ke index yang tidak ada (mis. tabel dibuat ulang oleh sync) membuat query error.
     */
    private function customerTable(): string|\Illuminate\Contracts\Database\Query\Expression
    {
        $index = 'tblcustomer_kd_customer_search_index';

        $exists = Cache::remember('afsel_tblcustomer_search_index_exists', 600, fn () => Schema::hasIndex('tblcustomer', $index));

        return $exists ? DB::raw("`tblcustomer` FORCE INDEX (`{$index}`)") : 'tblcustomer';
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

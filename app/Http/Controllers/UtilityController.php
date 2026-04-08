<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;


class UtilityController extends Controller
{

    

    public function start(Request $request)
    {
        $dataInsert['job_name'] = $request->job_name;
        $dataInsert['status'] = 'RUNNING';
        $dataInsert['start_time'] = now();

        DB::table('sync_logs')->insert($dataInsert);
    }

    public function finish(Request $request)
    {
        $log = DB::table('sync_logs')
                ->where('status', 'RUNNING')
                ->orderByDesc('start_time')
                ->first();

        if($log) {

            $dataUpdateLog['status'] = $request->status;
            $dataUpdateLog['end_time'] = now();
            $dataUpdateLog['message'] = $request->message;

            DB::table('sync_logs')->where('id', $log->id)->update($dataUpdateLog);
        }
        return response()->json(['status' => 'ok']);
    }

    public function status()
    {
        $running = DB::table('sync_logs')->where('status', 'RUNNING')->exists();

        return response()->json([
            'is_running' => $running
        ]);
    }

    public function sync_index()
    {
        return view('atpm.page_sync.sync_monitoring');
    }

    public function sync_logs_datatable()
    {
        $query = DB::table('sync_logs')->orderBy('start_time', 'desc')->get();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('start_time', function($row){
                return ($row->start_time == null) ? '-' : date('d-m-Y H:i:s', strtotime($row->start_time));
            })
            ->addColumn('end_time', function($row){
                return ($row->end_time == null) ? '-' : date('d-m-Y H:i:s', strtotime($row->end_time));
            })
            ->addColumn('duration', function($row){
                if (!$row->start_time || !$row->end_time) return '-';
                $diff = strtotime($row->end_time) - strtotime($row->start_time);
                if ($diff < 60)   return $diff . 's';
                if ($diff < 3600) return floor($diff / 60) . 'm ' . ($diff % 60) . 's';
                return floor($diff / 3600) . 'h ' . floor(($diff % 3600) / 60) . 'm ' . ($diff % 60) . 's';
            })
            ->rawColumns(['start_time', 'end_time', 'duration'])
            ->make(true);
    }

    public function sync_information()
    {
        return view('atpm.page_sync.sync_information');
    }




    
}

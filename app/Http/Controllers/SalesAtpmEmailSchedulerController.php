<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Repositories\EmailSchedulerCkdRepository;
use App\Services\EmailSchedulerCkdService;

class SalesAtpmEmailSchedulerController
{
    protected $emailSchedulerCkdRepo;
    protected $emailSchedulerCkdService;

    public function __construct(
        EmailSchedulerCkdRepository $EmailSchedulerCkdRepository,
        EmailSchedulerCkdService $EmailSchedulerCkdService
    )
    {
        $this->emailSchedulerCkdRepo = $EmailSchedulerCkdRepository;
        $this->emailSchedulerCkdService = $EmailSchedulerCkdService;
    }

    public function index()
    {
        $data['setup'] = $this->emailSchedulerCkdRepo->getSetup();

        return view('sales.atpm.page_email_scheduler_delivery_ckd.index', $data);
    }

    public function datatable()
    {
        $query = DB::table('tr_email_scheduler_ckd_log')->orderBy('id', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('date_sent', function($row){
                return $row->date_sent ? date('d-m-Y H:i:s', strtotime($row->date_sent)) : '-';
            })
            ->editColumn('email_to', function($row){
                return $row->email_to ?: '-';
            })
            ->addColumn('z_status_sent', function($row){
                if($row->status_sent == 'SUCCESS') {
                    return '<span class="badge bg-success">SUCCESS</span>';
                } else if($row->status_sent == 'FAILED') {
                    return '<span class="badge bg-danger">FAILED</span>';
                }
                return '<span class="badge bg-secondary">NOT SENT</span>';
            })
            ->addColumn('action', function($row){
                return '<button type="button" class="btn-fi btn-fi-primary btn-fi-sm btn-retry" data-id="'.$row->id.'">Retry Send Email</button>';
            })
            ->rawColumns(['z_status_sent', 'action'])
            ->make(true);
    }

    public function retryLogDatatable()
    {
        $query = DB::table('tr_email_scheduler_ckd_retry_log as retry')
            ->leftJoin('tr_email_scheduler_ckd_log as log', 'log.id', '=', 'retry.fk_email_scheduler_ckd_log')
            ->select('retry.*', 'log.created_at as log_created_at')
            ->orderBy('retry.id', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('retry_date', function($row){
                return date('d-m-Y H:i:s', strtotime($row->retry_date));
            })
            ->editColumn('date_sent', function($row){
                return $row->date_sent ? date('d-m-Y H:i:s', strtotime($row->date_sent)) : '-';
            })
            ->addColumn('data_date', function($row){
                return $row->log_created_at ? date('d-m-Y', strtotime($row->log_created_at)) : '-';
            })
            ->editColumn('email_to', function($row){
                return $row->email_to ?: '-';
            })
            ->editColumn('retry_by', function($row){
                return $row->retry_by ?: '-';
            })
            ->addColumn('z_status_sent', function($row){
                if($row->status_sent == 'SUCCESS') {
                    return '<span class="badge bg-success">SUCCESS</span>';
                } else if($row->status_sent == 'FAILED') {
                    return '<span class="badge bg-danger">FAILED</span>';
                }
                return '<span class="badge bg-secondary">NOT SENT</span>';
            })
            ->rawColumns(['z_status_sent'])
            ->make(true);
    }

    public function updateEmailTo(Request $request)
    {
        $emailTo = trim((string) $request->input('email_to'));

        if ($emailTo !== '' && count($this->emailSchedulerCkdService->parseEmails($emailTo)) == 0) {
            return response()->json(['status' => false, 'message' => 'Format email tidak valid'], 422);
        }

        $setup = $this->emailSchedulerCkdRepo->getSetup();

        if ($setup) {
            DB::table('ms_setup_email_scheduler_ckd')->where('id', $setup->id)->update([
                'email_to' => $emailTo,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('ms_setup_email_scheduler_ckd')->insert([
                'email_to' => $emailTo,
                'status_sent' => 'NOT SENT',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['status' => true, 'message' => 'Email To berhasil diupdate']);
    }

    public function retry(Request $request)
    {
        $result = $this->emailSchedulerCkdService->run($request->input('id'), session('user.name'));

        return response()->json($result);
    }
}

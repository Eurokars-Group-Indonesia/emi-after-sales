<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;

class SalesAtpmReportController
{
    public function salesPersonHistoryReport()
    {
        return view('sales.atpm.page_report.salesperson_report.salesperson_history_report');
    }

    public function salesPersonProductivityReport()
    {
        return view('sales.atpm.page_report.salesperson_report.salesperson_productivity_report');
    }

    public function salesPersonNationalProductivityReport()
    {
        return view('sales.atpm.page_report.salesperson_report.salesperson_national_productivity_report');
    }

}

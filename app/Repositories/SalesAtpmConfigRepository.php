<?php

namespace App\Repositories;
use Illuminate\Support\Facades\DB;

interface SalesAtpmConfigInterface
{
    public function findAll();
}

class SalesAtpmConfigRepository implements SalesAtpmConfigInterface
{
    public function findAll()
    {
        return DB::table("ms_sales_atpm_config")->get();
    }
}
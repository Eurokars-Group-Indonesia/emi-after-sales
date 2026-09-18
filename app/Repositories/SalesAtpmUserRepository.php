<?php

namespace App\Repositories;
use Illuminate\Support\Facades\DB;

interface SalesAtpmUserInterface
{
    public function findByKd($kd_atpm_user);
}

class SalesAtpmUserRepository implements SalesAtpmUserInterface
{
    public function findByKd($kd_atpm_user)
    {
        return DB::table("ms_sales_atpm_user")->where('kd_atpm_user', $kd_atpm_user)->first();
    }
}
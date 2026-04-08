<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

use App\Repositories\ModelRepository;

class TestController
{
    public function sp_test()
    {
        $pdo = DB::connection('mysql')->getPdo();
        $stmt = $pdo->prepare("CALL sp_trial()");
        $stmt->execute();

        // result 1
        $result1 = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt->nextRowset();

        // result 2
        $result2 = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        dd($result1, $result2);
    }
}

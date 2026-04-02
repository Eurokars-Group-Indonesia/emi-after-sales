<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tbluio')->insert([
            'kd_uio' => '1styears',
            'deskripsi' => '1st Years',
            'overall'=>false, 
            'nilai'=> 1,
            'is_active'=>true
        ]);

        DB::table('tbluio')->insert([
            'kd_uio' => '2ndyears',
            'deskripsi' => '2nd Years',
            'overall'=>false, 
            'nilai'=> 2,
            'is_active'=>true
        ]);

        DB::table('tbluio')->insert([
            'kd_uio' => '3rdyears',
            'deskripsi' => '3rd Years',
            'overall'=>false, 
            'nilai'=> 3,
            'is_active'=>true
        ]);

        DB::table('tbluio')->insert([
            'kd_uio' => '4thyears',
            'deskripsi' => '4th Years',
            'overall'=>false, 
            'nilai'=> 4,
            'is_active'=>true
        ]);

        DB::table('tbluio')->insert([
            'kd_uio' => '5thyears',
            'deskripsi' => '5th Years',
            'overall'=>false, 
            'nilai'=> 5,
            'is_active'=>true
        ]);

        DB::table('tbluio')->insert([
            'kd_uio' => '6thyears',
            'deskripsi' => '6th Years',
            'overall'=>false, 
            'nilai'=> 6,
            'is_active'=>true
        ]);

        DB::table('tbluio')->insert([
            'kd_uio' => '7thyears',
            'deskripsi' => '7th Years',
            'overall'=>false, 
            'nilai'=> 7,
            'is_active'=>true
        ]);

        
        DB::table('tbluio')->insert([
            'kd_uio' => 'overall3years',
            'deskripsi' => 'Overall 3 years',
            'overall'=>true, 
            'nilai'=> 3,
            'is_active'=>true
        ]);

        DB::table('tbluio')->insert([
            'kd_uio' => 'overall7years',
            'deskripsi' => 'Overall 7 years',
            'overall'=>true, 
            'nilai'=> 7,
            'is_active'=>true
        ]);
    }
}

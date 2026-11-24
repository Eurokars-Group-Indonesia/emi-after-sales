<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master agama (ms_religion). Dipakai Upload Service: kolom Agama di Excel → tblcustomer.fk_religion (religion_id).
 * ID mengikuti data WRS lama agar fk_religion tetap cocok dengan data customer hasil sync.
 */
return new class extends Migration
{
    private const DATA = [
        ['2014071700000000000000000001', 'Islam'],
        ['2019042500000000000000000002', 'Budha'],
        ['2019042500000000000000000003', 'Hindu'],
        ['2019042500000000000000000004', 'Konghucu'],
        ['2019042500000000000000000001', 'Protestan'],
        ['2019050600000000000000000001', 'Katolik'],
        ['2019050600000000000000000002', 'Lainnya'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('ms_religion')) {
            Schema::create('ms_religion', function (Blueprint $t) {
                $t->string('religion_id', 28)->primary();
                $t->string('religion', 100)->unique();
                $t->boolean('is_active')->default(true);
            });
        }

        foreach (self::DATA as [$id, $name]) {
            DB::table('ms_religion')->updateOrInsert(['religion_id' => $id], ['religion' => $name, 'is_active' => true]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_religion');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbluio', function (Blueprint $table) {

            $table->string('kd_uio')->unique();
            $table->string('deskripsi');
            $table->boolean('overall')->default(false);
            $table->integer('nilai');
            $table->boolean('is_active')->default(true);



            /*
            |--------------------------------------------------------------------------
            | INDEX
            |--------------------------------------------------------------------------
            */

            $table->index('kd_uio');


            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbluio');
    }
};

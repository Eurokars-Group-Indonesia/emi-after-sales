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
        Schema::create('tblmodel', function (Blueprint $table) {
            $table->string('kd_model')->primaryKey();
            $table->text('nm_model');
            $table->text('jenis_kendaraan')->nullable();
            $table->boolean('is_wrs')->default(false);
            $table->text('model_year')->nullable();
            $table->text('engine_type')->nullable();
            $table->text('vehicle_type')->nullable();
            $table->text('wmi')->nullable();
            $table->text('hand_over_picture')->nullable();
            $table->text('hand_over_picture_ext')->nullable();
            $table->boolean('is_wrs_aftersales')->default(true);

            $table->index('kd_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblmodel');
    }
};

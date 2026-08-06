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
        Schema::create('ms_sales_atpm_user', function (Blueprint $table) {
            $table->string('kd_atpm_user')->primary();
            $table->text('nm_atpm_user');
            $table->text('username');
            $table->text('password');
            $table->text('fk_atpm_level');
            $table->text('fk_atpm_department');
            $table->text('email')->nullable();
            $table->text('picture')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('picture_ext')->nullable();
            $table->datetime('tgl_masuk')->nullable();
            
            $table->unsignedBigInteger('created_by');
            $table->datetime('date_created');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->datetime('date_updated')->nullable();


            $table->index('kd_atpm_user');
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ms_sales_atpm_user');
    }
};

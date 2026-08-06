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
        Schema::create('tr_sales_atpm_user_menu', function (Blueprint $table) {
            $table->id();
            $table->text('fk_kd_atpm_user');
            $table->unsignedBigInteger('fk_sales_atpm_master_menu');
            $table->boolean('is_active')->default(true);
            
            $table->unsignedBigInteger('created_by');
            $table->datetime('date_created');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->datetime('date_updated')->nullable();


            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_sales_atpm_user_menu');
    }
};

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
        Schema::create('ms_sales_atpm_master_menu', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('route')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->unsignedBigInteger('created_by');
            $table->datetime('date_created');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->datetime('date_updated')->nullable();


            $table->foreign('parent_id')->references('id')->on('ms_sales_atpm_master_menu')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ms_sales_atpm_master_menu');
    }
};

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
        Schema::create('ms_sales_atpm_config', function (Blueprint $table) {
            $table->id();
            $table->string('config');
            $table->string('setup');
            $table->text('description');
            $table->boolean('is_active')->default(true);
            
            $table->string('created_by', 255);
            $table->datetime('date_created');
            $table->string('updated_by', 255)->nullable();
            $table->datetime('date_updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ms_sales_atpm_config');
    }
};

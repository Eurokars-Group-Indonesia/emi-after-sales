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
        Schema::create('tblcategory_vehicles', function (Blueprint $table) {
            $table->string('kd_category_vehicles');
            $table->text('nm_category_vehicles');
            $table->boolean('is_active')->default(true);
            $table->text('jenis')->nullable();

            // Index gabungan (composite index)
            $table->index(['kd_category_vehicles']);
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblcategory_vehicles');
    }
};

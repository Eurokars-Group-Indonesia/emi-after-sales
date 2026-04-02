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
        Schema::create('tblcategory_2_vehicles', function (Blueprint $table) {
            $table->string('kd_category_2_vehicles');
            $table->text('nm_category_2_vehicles');
            $table->text('fk_category_vehicles');
            $table->boolean('is_active')->default(true);

            // Index gabungan (composite index)
            $table->index(['kd_category_2_vehicles']);
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblcategory_2_vehicles');
    }
};

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
        Schema::create('tbldealer', function (Blueprint $table) {
            $table->string('kd_dealer');
            $table->text('nm_dealer');
            $table->text('fk_dealer_region');
            $table->text('fk_dealer_group');
            $table->boolean('is_active')->default(false);

            // Index gabungan (composite index)
            $table->index(['kd_dealer']);
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbldealer');
    }
};

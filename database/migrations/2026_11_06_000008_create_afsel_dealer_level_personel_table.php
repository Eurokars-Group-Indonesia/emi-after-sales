<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master Level Personel After Sales (dealer).
 * Sumber: master_aftersales.tbllevel_personil_aftersales
 *   kd_level_personil_aftersales → dealer_level_personel_id
 *   nm_level_personil_aftersales → dealer_level_personel
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afsel_dealer_level_personel', function (Blueprint $t) {
            $t->string('dealer_level_personel_id', 255)->primary();
            $t->string('dealer_level_personel', 255);
            $t->boolean('is_active')->default(true);
            $t->text('jenis')->nullable()->comment('Direct / Indirect');

            $t->index('is_active', 'afsel_dealer_level_personel_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afsel_dealer_level_personel');
    }
};

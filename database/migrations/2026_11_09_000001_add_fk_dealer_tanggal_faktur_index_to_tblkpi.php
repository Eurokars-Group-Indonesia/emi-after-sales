<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index gabungan untuk menu Data Service dealer: filter per dealer + urut/rentang tanggal service.
 * Catatan: jika tblkpi dibuat ulang oleh proses sync (mis. Pentaho drop/create), index ini perlu dibuat lagi.
 */
return new class extends Migration
{
    private const INDEX = 'tblkpi_fk_dealer_tanggal_faktur_index';

    public function up(): void
    {
        if (Schema::hasIndex('tblkpi', self::INDEX)) {
            return;
        }

        Schema::table('tblkpi', function (Blueprint $table) {
            $table->index(['fk_dealer', 'tanggal_faktur'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (!Schema::hasIndex('tblkpi', self::INDEX)) {
            return;
        }

        Schema::table('tblkpi', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }
};

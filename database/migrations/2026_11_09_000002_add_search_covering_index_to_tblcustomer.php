<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index covering untuk join tblkpi → tblcustomer di menu Data Service dealer.
 * Search no. polisi / VIN / model cukup membaca index ini tanpa membaca baris tblcustomer
 * yang lebar (alamat, nm_pemakai bertipe text).
 * Catatan: jika tblcustomer dibuat ulang oleh proses sync (mis. Pentaho drop/create), index ini perlu dibuat lagi.
 */
return new class extends Migration
{
    private const INDEX = 'tblcustomer_kd_customer_search_index';

    public function up(): void
    {
        if (Schema::hasIndex('tblcustomer', self::INDEX)) {
            return;
        }

        Schema::table('tblcustomer', function (Blueprint $table) {
            $table->index(['kd_customer', 'nomor_polisi', 'no_vin', 'fk_model'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (!Schema::hasIndex('tblcustomer', self::INDEX)) {
            return;
        }

        Schema::table('tblcustomer', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }
};

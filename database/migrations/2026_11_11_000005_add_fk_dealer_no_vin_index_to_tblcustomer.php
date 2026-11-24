<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upload Service mencari customer per (fk_dealer, no_vin) untuk setiap baris Excel.
 * Tanpa index ini MySQL memindai seluruh customer milik dealer untuk setiap pencarian.
 */
return new class extends Migration
{
    private const INDEX = 'tblcustomer_fk_dealer_no_vin_index';

    public function up(): void
    {
        if (!Schema::hasIndex('tblcustomer', self::INDEX)) {
            Schema::table('tblcustomer', fn (Blueprint $t) => $t->index(['fk_dealer', 'no_vin'], self::INDEX));
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('tblcustomer', self::INDEX)) {
            Schema::table('tblcustomer', fn (Blueprint $t) => $t->dropIndex(self::INDEX));
        }
    }
};

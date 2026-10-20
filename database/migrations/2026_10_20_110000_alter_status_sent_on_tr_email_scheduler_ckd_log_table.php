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
        Schema::table('tr_email_scheduler_ckd_log', function (Blueprint $table) {
            $table->enum('status_sent', ['NOT SENT', 'FAILED', 'SUCCESS'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tr_email_scheduler_ckd_log', function (Blueprint $table) {
            $table->enum('status_sent', ['NOT SENT', 'SUCCESS'])->change();
        });
    }
};

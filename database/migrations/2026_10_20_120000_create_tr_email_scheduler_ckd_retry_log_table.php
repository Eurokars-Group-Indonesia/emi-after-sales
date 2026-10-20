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
        Schema::create('tr_email_scheduler_ckd_retry_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_email_scheduler_ckd_log');
            $table->text('email_to')->nullable();
            $table->datetime('retry_date');
            $table->string('retry_by')->nullable();
            $table->datetime('date_sent')->nullable();
            $table->enum('status_sent', ['NOT SENT', 'FAILED', 'SUCCESS']);
            $table->timestamps();

            $table->index('fk_email_scheduler_ckd_log', 'idx_ckd_retry_log_fk_log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_email_scheduler_ckd_retry_log');
    }
};

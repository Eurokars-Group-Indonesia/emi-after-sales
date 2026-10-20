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
        Schema::create('tr_email_scheduler_ckd_log', function (Blueprint $table) {
            $table->id();
            $table->string('job_name');
            $table->datetime('date_sent')->nullable();
            $table->enum('status_sent', ['NOT SENT','FAILED', 'SUCCESS']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_email_scheduler_ckd_log');
    }
};

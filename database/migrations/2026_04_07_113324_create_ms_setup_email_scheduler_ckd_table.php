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
        Schema::create('ms_setup_email_scheduler_ckd', function (Blueprint $table) {
            $table->id();
            $table->string('email_to');
            $table->datetime('date_sent')->nullable();
            $table->enum('status_sent', ['NOT SENT','SUCCESS']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ms_setup_email_scheduler_ckd');
    }
};

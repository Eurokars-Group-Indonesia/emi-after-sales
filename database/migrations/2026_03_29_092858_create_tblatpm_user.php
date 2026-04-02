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
        Schema::create('tblatpm_user', function (Blueprint $table) {
            $table->string('kd_atpm_user')->primary();
            $table->text('nm_atpm_user');
            $table->text('username');
            $table->text('password');
            $table->text('fk_atpm_level');
            $table->text('fk_atpm_department');
            $table->text('email')->nullable();
            $table->text('picture')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('picture_ext')->nullable();
            $table->datetime('tgl_masuk')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblatpm_user');
    }
};

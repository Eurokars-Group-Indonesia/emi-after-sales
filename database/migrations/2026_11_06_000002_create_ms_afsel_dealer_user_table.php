<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * User Dealer untuk WRS Connect - After Sales.
 * Dipakai untuk login lokal (menggantikan API eurokars-wrs-login-service).
 *
 * Catatan: tidak memakai foreign key constraint (konvensi project),
 * relasi dijaga di level aplikasi + index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ms_afsel_dealer_user', function (Blueprint $table) {
            $table->string('kd_dealer_user', 255)->primary();
            $table->text('nm_dealer_user');
            $table->text('username');
            $table->text('password');
            $table->text('email')->nullable();
            $table->text('picture')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('picture_ext')->nullable();
            $table->dateTime('tgl_masuk')->nullable();

            // Field tambahan (sumber: master_aftersales.tbldealer_user)
            $table->string('fk_dealer', 255)->nullable();       // -> tbldealer.kd_dealer
            $table->string('fk_dealer_level', 255)->nullable();

            // Field tambahan untuk keamanan login
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->dateTime('locked_until')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->dateTime('password_changed_at')->nullable();

            $table->string('created_by', 255);
            $table->dateTime('date_created');
            $table->string('updated_by', 255)->nullable();
            $table->dateTime('date_updated')->nullable();

            // ===== Index =====
            $table->index('kd_dealer_user'); // ms_afsel_dealer_user_kd_dealer_user_index
            // Kolom TEXT di MySQL wajib memakai prefix length untuk index
            $table->rawIndex('username(191)', 'ms_afsel_dealer_user_username_index');
            $table->rawIndex('email(191)', 'ms_afsel_dealer_user_email_index');
            $table->index('is_active', 'ms_afsel_dealer_user_is_active_index');
            $table->index(['fk_dealer', 'is_active'], 'ms_afsel_dealer_user_fk_dealer_active_index');
            $table->index('fk_dealer_level', 'ms_afsel_dealer_user_fk_dealer_level_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_afsel_dealer_user');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menandai user ATPM yang mengganti email sendiri lewat menu Profile.
 * afsel:sync-users tidak menimpa email user yang kolom ini terisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ms_afsel_atpm_user', 'email_changed_at')) {
            Schema::table('ms_afsel_atpm_user', function (Blueprint $t) {
                $t->dateTime('email_changed_at')->nullable()->after('password_changed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ms_afsel_atpm_user', 'email_changed_at')) {
            Schema::table('ms_afsel_atpm_user', fn (Blueprint $t) => $t->dropColumn('email_changed_at'));
        }
    }
};

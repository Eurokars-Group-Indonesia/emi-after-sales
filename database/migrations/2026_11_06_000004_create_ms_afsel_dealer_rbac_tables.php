<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC After Sales - Dealer
 *
 *   ms_afsel_dealer_roles             : master role
 *   ms_afsel_dealer_permissions       : master permission (slug)
 *   ms_afsel_dealer_role_permissions  : role  <-> permission
 *   ms_afsel_dealer_user_roles        : user  <-> role (multi role, bisa expired)
 *   ms_afsel_dealer_menus             : master menu sidebar (hierarki parent_id)
 *   ms_afsel_dealer_menu_roles        : menu  <-> role
 *   ms_afsel_dealer_menu_users        : override menu per user (is_granted 1 = tambah, 0 = cabut)
 *
 * Tanpa foreign key constraint (konvensi project), relasi dijaga lewat index.
 */
return new class extends Migration
{
    private string $p = 'ms_afsel_dealer_';
    private string $userKey = 'kd_dealer_user';

    public function up(): void
    {
        $p = $this->p;
        $userKey = $this->userKey;

        // ============ roles ============
        Schema::create($p . 'roles', function (Blueprint $t) use ($p) {
            $t->id();
            $t->string('name', 100);
            $t->string('slug', 100);
            $t->string('description', 500)->nullable();
            $t->integer('level')->default(100)->comment('Semakin kecil semakin tinggi privilege');
            $t->boolean('is_system')->default(false)->comment('1 = super admin, bypass semua permission & menu');
            $t->boolean('is_active')->default(true);
            $t->string('created_by', 255);
            $t->dateTime('date_created');
            $t->string('updated_by', 255)->nullable();
            $t->dateTime('date_updated')->nullable();

            $t->unique('name', $p . 'roles_name_unique');
            $t->unique('slug', $p . 'roles_slug_unique');
            $t->index(['is_active', 'level'], $p . 'roles_active_level_index');
        });

        // ============ permissions ============
        Schema::create($p . 'permissions', function (Blueprint $t) use ($p) {
            $t->id();
            $t->string('name', 100);
            $t->string('slug', 100);
            $t->string('group_name', 50)->nullable();
            $t->string('description', 500)->nullable();
            $t->string('created_by', 255);
            $t->dateTime('date_created');
            $t->string('updated_by', 255)->nullable();
            $t->dateTime('date_updated')->nullable();

            $t->unique('slug', $p . 'permissions_slug_unique');
            $t->index('group_name', $p . 'permissions_group_name_index');
        });

        // ============ role_permissions ============
        Schema::create($p . 'role_permissions', function (Blueprint $t) use ($p) {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
            $t->string('created_by', 255);
            $t->dateTime('date_created');

            $t->unique(['role_id', 'permission_id'], $p . 'role_permissions_unique');
            $t->index('permission_id', $p . 'role_permissions_permission_index');
        });

        // ============ user_roles ============
        Schema::create($p . 'user_roles', function (Blueprint $t) use ($p, $userKey) {
            $t->id();
            $t->string($userKey, 255);
            $t->unsignedBigInteger('role_id');
            $t->string('assigned_by', 255)->nullable();
            $t->dateTime('assigned_at');
            $t->dateTime('expires_at')->nullable();

            $t->unique([$userKey, 'role_id'], $p . 'user_roles_unique');
            $t->index('role_id', $p . 'user_roles_role_index');
            $t->index([$userKey, 'expires_at'], $p . 'user_roles_user_expires_index');
        });

        // ============ menus ============
        Schema::create($p . 'menus', function (Blueprint $t) use ($p) {
            $t->id();
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->string('name', 100)->comment('Kode unik menu');
            $t->string('label', 255);
            $t->string('route_name', 255)->nullable();
            $t->string('url', 500)->nullable();
            $t->string('icon', 100)->nullable();
            $t->integer('sort_order')->default(0);
            $t->string('permission_slug', 100)->nullable()->comment('Opsional: menu hanya tampil jika punya permission ini');
            $t->boolean('is_active')->default(true);
            $t->string('created_by', 255);
            $t->dateTime('date_created');
            $t->string('updated_by', 255)->nullable();
            $t->dateTime('date_updated')->nullable();

            $t->unique('name', $p . 'menus_name_unique');
            $t->index(['parent_id', 'sort_order', 'is_active'], $p . 'menus_parent_sort_active_index');
            $t->index('route_name', $p . 'menus_route_name_index');
        });

        // ============ menu_roles ============
        Schema::create($p . 'menu_roles', function (Blueprint $t) use ($p) {
            $t->id();
            $t->unsignedBigInteger('menu_id');
            $t->unsignedBigInteger('role_id');
            $t->string('created_by', 255);
            $t->dateTime('date_created');

            $t->unique(['menu_id', 'role_id'], $p . 'menu_roles_unique');
            $t->index('role_id', $p . 'menu_roles_role_index');
        });

        // ============ menu_users (override per user) ============
        Schema::create($p . 'menu_users', function (Blueprint $t) use ($p, $userKey) {
            $t->id();
            $t->unsignedBigInteger('menu_id');
            $t->string($userKey, 255);
            $t->boolean('is_granted')->default(true)->comment('1 = tambah akses, 0 = cabut akses');
            $t->string('created_by', 255);
            $t->dateTime('date_created');
            $t->string('updated_by', 255)->nullable();
            $t->dateTime('date_updated')->nullable();

            $t->unique(['menu_id', $userKey], $p . 'menu_users_unique');
            $t->index($userKey, $p . 'menu_users_user_index');
        });
    }

    public function down(): void
    {
        foreach (['menu_users', 'menu_roles', 'menus', 'user_roles', 'role_permissions', 'permissions', 'roles'] as $table) {
            Schema::dropIfExists($this->p . $table);
        }
    }
};

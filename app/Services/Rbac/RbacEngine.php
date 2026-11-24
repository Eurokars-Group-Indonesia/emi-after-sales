<?php

namespace App\Services\Rbac;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mesin RBAC WRS Connect (diadopsi dari eurokars-exam), dipakai oleh:
 *  - App\Services\Afsel\AfselRbacService  : WRS Connect After Sales (ATPM & Dealer)
 *  - App\Services\Sales\SalesRbacService  : WRS Connect Sales       (ATPM & Dealer)
 * Tiap turunan punya tabel, session key role aktif, dan prefix cache sendiri sehingga
 * RBAC After Sales dan Sales tidak saling bercampur.
 *
 * Konsep:
 *  - User bisa punya banyak role (user_roles, opsional expires_at).
 *  - Dalam satu session hanya SATU role yang aktif (dipilih saat login / switch role).
 *  - Permission dievaluasi dari role aktif (role_permissions).
 *  - Role is_system = super admin → bypass semua permission & lihat semua menu.
 *  - Menu = menu milik role aktif (menu_roles) + override per user (menu_users):
 *      is_granted = 1 → tambah, is_granted = 0 → cabut.
 *    Child menu otomatis hilang jika parent-nya tidak punya akses.
 *
 * Semua method menerima $type = key dari static::TYPES ('atpm' | 'dealer').
 */
abstract class RbacEngine
{
    /** Session key role aktif (wajib di-override). */
    public const SESSION_ROLE_KEY = '';

    /**
     * Konfigurasi per tipe user (wajib di-override):
     *   prefix, user_table, user_key, user_name, role_model, menu_model, home_route, app
     */
    public const TYPES = [];

    /** Prefix key cache (versi & data) agar cache tiap aplikasi terpisah. */
    protected const CACHE_PREFIX = 'rbac';

    /** Tipe user yang menjadi pengelola halaman Access Control. */
    public const ADMIN_TYPE = 'atpm';

    /** Per-request cache */
    protected array $rolesCache = [];
    private array $permissionCache = [];

    /** TTL cache menu & permission (detik). */
    abstract protected function cacheTtl(): int;

    // ─────────────────────────────────────────────────────────────
    // KONFIGURASI
    // ─────────────────────────────────────────────────────────────

    public function types(): array
    {
        return static::TYPES;
    }

    public function hasType(?string $type): bool
    {
        return $type !== null && isset(static::TYPES[$type]);
    }

    public function config(string $type): array
    {
        if (!isset(static::TYPES[$type])) {
            throw new \InvalidArgumentException('Tipe user RBAC tidak dikenal: ' . $type);
        }

        return static::TYPES[$type];
    }

    public function roleSessionKey(string $type): string
    {
        return static::SESSION_ROLE_KEY;
    }

    public function table(string $type, string $name): string
    {
        return $this->config($type)['prefix'] . $name;
    }

    // ─────────────────────────────────────────────────────────────
    // ROLE
    // ─────────────────────────────────────────────────────────────

    /**
     * Semua role aktif & belum expired milik user, urut level (privilege tertinggi dulu).
     */
    public function getAssignedRoles(string $type, string $userId): Collection
    {
        $key = $type . ':' . $userId;

        if (!array_key_exists($key, $this->rolesCache)) {
            $cfg = $this->config($type);
            /** @var class-string<Model> $roleModel */
            $roleModel = $cfg['role_model'];
            $ur = $this->table($type, 'user_roles');
            $r  = $this->table($type, 'roles');

            $this->rolesCache[$key] = $roleModel::query()
                ->select("{$r}.*")
                ->join($ur, "{$ur}.role_id", '=', "{$r}.id")
                ->where("{$ur}.{$cfg['user_key']}", $userId)
                ->where("{$r}.is_active", true)
                ->where(function ($q) use ($ur) {
                    $q->whereNull("{$ur}.expires_at")->orWhere("{$ur}.expires_at", '>', now());
                })
                ->orderBy("{$r}.level")
                ->orderBy("{$r}.name")
                ->get();
        }

        return $this->rolesCache[$key];
    }

    /**
     * Role aktif di session. Jika user hanya punya 1 role → otomatis dipilih.
     * Divalidasi ulang setiap request (role bisa dicabut / expired / dinonaktifkan).
     */
    public function getActiveRole(string $type, string $userId): ?Model
    {
        $roles    = $this->getAssignedRoles($type, $userId);
        $sessionKey = $this->roleSessionKey($type);
        $activeId = (int) session($sessionKey);
        $active   = $activeId ? $roles->firstWhere('id', $activeId) : null;

        if (!$active && $roles->count() === 1) {
            $active = $roles->first();
            session([$sessionKey => $active->id]);
        }

        if (!$active && $activeId) {
            session()->forget($sessionKey);
        }

        return $active;
    }

    public function setActiveRole(string $type, string $userId, int $roleId): Model
    {
        $role = $this->getAssignedRoles($type, $userId)->firstWhere('id', $roleId);

        if (!$role) {
            throw new \RuntimeException('Role tidak tersedia untuk user ini.');
        }

        session([$this->roleSessionKey($type) => $role->id]);
        $this->permissionCache = [];

        return $role;
    }

    public function isSuperAdmin(string $type, string $userId): bool
    {
        return (bool) $this->getActiveRole($type, $userId)?->is_system;
    }

    // ─────────────────────────────────────────────────────────────
    // PERMISSION
    // ─────────────────────────────────────────────────────────────

    /**
     * Cek permission berdasarkan role aktif.
     */
    public function userHasPermission(string $type, string $userId, string $slug): bool
    {
        $role = $this->getActiveRole($type, $userId);
        if (!$role) {
            return false;
        }

        if ($role->is_system) {
            return true;
        }

        return in_array($slug, $this->rolePermissionSlugs($type, $role->id), true);
    }

    /**
     * Slug permission milik role (cache lintas request, di-invalidate via versi cache).
     */
    public function rolePermissionSlugs(string $type, int $roleId): array
    {
        $key = $type . ':' . $roleId;

        if (!array_key_exists($key, $this->permissionCache)) {
            $this->permissionCache[$key] = Cache::remember(
                $this->cacheKey($type, "role_perm_{$roleId}"),
                $this->cacheTtl(),
                function () use ($type, $roleId) {
                    $rp = $this->table($type, 'role_permissions');
                    $p  = $this->table($type, 'permissions');

                    return DB::table($rp)
                        ->join($p, "{$p}.id", '=', "{$rp}.permission_id")
                        ->where("{$rp}.role_id", $roleId)
                        ->pluck("{$p}.slug")
                        ->all();
                }
            );
        }

        return $this->permissionCache[$key];
    }

    // ─────────────────────────────────────────────────────────────
    // MENU
    // ─────────────────────────────────────────────────────────────

    /**
     * Menu (flat, sudah difilter) untuk user berdasarkan role aktif + override.
     *
     * Algoritma:
     * 1. Role aktif is_system → semua menu aktif.
     * 2. menu_ids dari menu_roles (role aktif).
     * 3. Terapkan override menu_users (is_granted 1 tambah, 0 cabut).
     * 4. Filter menu aktif + permission_slug (jika diisi).
     * 5. Buang child yang parent-nya tidak punya akses (berapa pun kedalamannya).
     * 6. Urut sort_order, label.
     */
    public function calculateMenuAccess(string $type, string $userId): Collection
    {
        $cfg = $this->config($type);
        /** @var class-string<Model> $menuModel */
        $menuModel = $cfg['menu_model'];

        $role = $this->getActiveRole($type, $userId);
        if (!$role) {
            return collect();
        }

        if ($role->is_system) {
            return $menuModel::where('is_active', true)->orderBy('sort_order')->orderBy('label')->get();
        }

        $accessSet = DB::table($this->table($type, 'menu_roles'))
            ->where('role_id', $role->id)
            ->pluck('menu_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $overrides = DB::table($this->table($type, 'menu_users'))
            ->where($cfg['user_key'], $userId)
            ->get(['menu_id', 'is_granted']);

        foreach ($overrides as $override) {
            if ($override->is_granted) {
                $accessSet[(int) $override->menu_id] = true;
            } else {
                unset($accessSet[(int) $override->menu_id]);
            }
        }

        if ($accessSet->isEmpty()) {
            return collect();
        }

        $permissions = $this->rolePermissionSlugs($type, $role->id);

        $menus = $menuModel::where('is_active', true)
            ->whereIn('id', $accessSet->keys()->all())
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->filter(fn ($m) => !$m->permission_slug || in_array($m->permission_slug, $permissions, true))
            ->values();

        do {
            $count = $menus->count();
            $ids   = $menus->pluck('id')->all();
            $menus = $menus->filter(fn ($m) => $m->parent_id === null || in_array($m->parent_id, $ids))->values();
        } while ($menus->count() !== $count);

        return $menus;
    }

    /**
     * Menu dalam bentuk tree (relasi `children` sudah terisi) untuk sidebar.
     */
    public function getMenuTree(string $type, string $userId): Collection
    {
        $roleId = (int) $this->getActiveRole($type, $userId)?->id;

        try {
            $flat = Cache::remember(
                $this->cacheKey($type, "menu_{$userId}_r{$roleId}"),
                $this->cacheTtl(),
                fn () => $this->calculateMenuAccess($type, $userId)
            );
        } catch (\Throwable $e) {
            Log::error('[' . static::class . '] gagal memuat menu: ' . $e->getMessage());
            return collect();
        }

        // ID menu yang di master punya sub menu aktif (untuk mendeteksi grup yang semua child-nya tidak boleh diakses)
        $menuModel = $this->config($type)['menu_model'];
        $parentsWithChildren = Cache::remember(
            $this->cacheKey($type, 'menu_parent_ids'),
            $this->cacheTtl(),
            fn () => $menuModel::where('is_active', true)->whereNotNull('parent_id')
                ->distinct()->pluck('parent_id')->map(fn ($id) => (int) $id)->all()
        );

        return $this->buildTree($flat, null, array_flip($parentsWithChildren));
    }

    public function userCanAccessRoute(string $type, string $userId, string $routeName): bool
    {
        return $this->calculateMenuAccess($type, $userId)->contains('route_name', $routeName);
    }

    /**
     * Susun tree.
     * Menu grup (tanpa route & url) disembunyikan HANYA jika di master punya sub menu tetapi tidak ada
     * satu pun yang bisa diakses user. Menu grup yang memang belum punya sub menu (baru dibuat) tetap tampil.
     *
     * @param array $parentsWithChildren [menu_id => index] menu yang di master punya child aktif
     */
    private function buildTree(Collection $flat, ?int $parentId = null, array $parentsWithChildren = []): Collection
    {
        return $flat
            ->filter(fn ($m) => ($m->parent_id === null ? null : (int) $m->parent_id) === $parentId)
            ->map(function ($menu) use ($flat, $parentsWithChildren) {
                $menu->setRelation('children', $this->buildTree($flat, $menu->id, $parentsWithChildren));
                return $menu;
            })
            ->reject(fn ($m) => !$m->route_name && !$m->url && $m->children->isEmpty()
                && isset($parentsWithChildren[(int) $m->id]))
            ->values();
    }

    // ─────────────────────────────────────────────────────────────
    // MANAJEMEN AKSES (dipakai seeder / command / halaman admin nanti)
    // ─────────────────────────────────────────────────────────────

    /** Sync role user (replace strategy). */
    public function syncUserRoles(string $type, string $userId, array $roleIds, string $by, ?string $expiresAt = null): void
    {
        $cfg = $this->config($type);
        $ur  = $this->table($type, 'user_roles');

        DB::transaction(function () use ($ur, $cfg, $userId, $roleIds, $by, $expiresAt) {
            DB::table($ur)->where($cfg['user_key'], $userId)->whereNotIn('role_id', $roleIds)->delete();

            foreach (array_unique(array_map('intval', $roleIds)) as $roleId) {
                DB::table($ur)->updateOrInsert(
                    [$cfg['user_key'] => $userId, 'role_id' => $roleId],
                    ['assigned_by' => $by, 'assigned_at' => now(), 'expires_at' => $expiresAt]
                );
            }
        });

        $this->flushCache($type);
    }

    /** Sync permission ke role (replace strategy). */
    public function syncRolePermissions(string $type, int $roleId, array $permissionIds, string $by): void
    {
        $this->syncPivot($this->table($type, 'role_permissions'), 'role_id', $roleId, 'permission_id', $permissionIds, $by);
        $this->flushCache($type);
    }

    /** Sync menu ke role (replace strategy). */
    public function syncRoleMenus(string $type, int $roleId, array $menuIds, string $by): void
    {
        $this->syncPivot($this->table($type, 'menu_roles'), 'role_id', $roleId, 'menu_id', $menuIds, $by);
        $this->flushCache($type);
    }

    /** Simpan override menu per user. $isGranted null = hapus override. */
    public function saveUserMenuOverride(string $type, string $userId, int $menuId, ?bool $isGranted, string $by): void
    {
        $cfg = $this->config($type);
        $mu  = $this->table($type, 'menu_users');
        $where = ['menu_id' => $menuId, $cfg['user_key'] => $userId];

        if ($isGranted === null) {
            DB::table($mu)->where($where)->delete();
        } elseif (DB::table($mu)->where($where)->exists()) {
            DB::table($mu)->where($where)->update(['is_granted' => $isGranted, 'updated_by' => $by, 'date_updated' => now()]);
        } else {
            DB::table($mu)->insert($where + ['is_granted' => $isGranted, 'created_by' => $by, 'date_created' => now()]);
        }

        $this->flushCache($type);
    }

    /**
     * Simpan role user. $roles = [role_id => expires_at|null].
     * Role yang sudah ada hanya diubah expires_at-nya (assigned_by/at dipertahankan).
     */
    public function saveUserRoles(string $type, string $userId, array $roles, string $by): void
    {
        $key = $this->config($type)['user_key'];
        $ur  = $this->table($type, 'user_roles');

        DB::transaction(function () use ($ur, $key, $userId, $roles, $by) {
            DB::table($ur)->where($key, $userId)->whereNotIn('role_id', array_keys($roles))->delete();

            foreach ($roles as $roleId => $expiresAt) {
                $where = [$key => $userId, 'role_id' => (int) $roleId];

                if (DB::table($ur)->where($where)->exists()) {
                    DB::table($ur)->where($where)->update(['expires_at' => $expiresAt]);
                } else {
                    DB::table($ur)->insert($where + ['assigned_by' => $by, 'assigned_at' => now(), 'expires_at' => $expiresAt]);
                }
            }
        });

        $this->flushCache($type);
    }

    /**
     * Simpan override menu user secara massal. $map = [menu_id => 'default'|'grant'|'revoke'].
     */
    public function syncUserMenuOverrides(string $type, string $userId, array $map, string $by): void
    {
        $key = $this->config($type)['user_key'];
        $mu  = $this->table($type, 'menu_users');

        DB::transaction(function () use ($mu, $key, $userId, $map, $by) {
            foreach ($map as $menuId => $mode) {
                $where = ['menu_id' => (int) $menuId, $key => $userId];

                if ($mode === 'default') {
                    DB::table($mu)->where($where)->delete();
                    continue;
                }

                $granted = $mode === 'grant';
                if (DB::table($mu)->where($where)->exists()) {
                    DB::table($mu)->where($where)->update(['is_granted' => $granted, 'updated_by' => $by, 'date_updated' => now()]);
                } else {
                    DB::table($mu)->insert($where + ['is_granted' => $granted, 'created_by' => $by, 'date_created' => now()]);
                }
            }
        });

        $this->flushCache($type);
    }

    /**
     * Tambahkan semua parent dari menu yang dipilih (agar child tidak hilang dari sidebar).
     */
    public function withMenuAncestors(string $type, array $menuIds): array
    {
        $parents = DB::table($this->table($type, 'menus'))->pluck('parent_id', 'id');
        $result  = [];

        foreach ($menuIds as $id) {
            $id = (int) $id;
            $guard = 0;
            while ($id && !isset($result[$id]) && $guard++ < 50) {
                $result[$id] = true;
                $id = (int) ($parents[$id] ?? 0);
            }
        }

        return array_keys($result);
    }

    /**
     * Anti privilege escalation untuk halaman admin RBAC.
     * Pengelola = user tipe ADMIN_TYPE (ATPM) yang sedang login (role aktif).
     *  - Role is_system hanya boleh dikelola / di-assign oleh Super Admin.
     *  - Untuk role ATPM: non Super Admin hanya boleh mengelola role dengan level >= level role aktifnya.
     */
    public function canManageRole(string $type, object $role, string $actorId): bool
    {
        if ($this->isSuperAdmin(static::ADMIN_TYPE, $actorId)) {
            return true;
        }

        if ($role->is_system) {
            return false;
        }

        if ($type === static::ADMIN_TYPE) {
            $actorLevel = (int) $this->getActiveRole(static::ADMIN_TYPE, $actorId)?->level;
            return (int) $role->level >= $actorLevel;
        }

        return true;
    }

    private function syncPivot(string $table, string $ownerCol, int $ownerId, string $itemCol, array $itemIds, string $by): void
    {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));

        DB::transaction(function () use ($table, $ownerCol, $ownerId, $itemCol, $itemIds, $by) {
            DB::table($table)->where($ownerCol, $ownerId)->whereNotIn($itemCol, $itemIds)->delete();

            $existing = DB::table($table)->where($ownerCol, $ownerId)->pluck($itemCol)->map(fn ($v) => (int) $v)->all();
            $rows = [];
            foreach (array_diff($itemIds, $existing) as $itemId) {
                $rows[] = [$ownerCol => $ownerId, $itemCol => $itemId, 'created_by' => $by, 'date_created' => now()];
            }
            if ($rows) {
                DB::table($table)->insert($rows);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────
    // CACHE
    // ─────────────────────────────────────────────────────────────

    /**
     * Semua cache menu & permission per tipe memakai "versi".
     * Naikkan versi = invalidate seluruh cache tipe tsb tanpa perlu tahu key-nya.
     */
    public function flushCache(?string $type = null): void
    {
        foreach ($type ? [$type] : array_keys(static::TYPES) as $t) {
            $versionKey = static::CACHE_PREFIX . "_version_{$t}";
            Cache::forever($versionKey, (int) Cache::get($versionKey, 0) + 1);
        }

        $this->rolesCache = [];
        $this->permissionCache = [];
    }

    private function cacheKey(string $type, string $suffix): string
    {
        return static::CACHE_PREFIX . "_{$type}_v" . (int) Cache::get(static::CACHE_PREFIX . "_version_{$type}", 0) . "_{$suffix}";
    }
}

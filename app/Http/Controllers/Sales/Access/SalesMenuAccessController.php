<?php

namespace App\Http\Controllers\Sales\Access;

use App\Http\Controllers\Afsel\AfselAccessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Access Configuration Sales (diadopsi dari eurokars-exam: Menu Access Configuration).
 *
 *  - Per Role : centang menu per role               → ms_sales_{type}_menu_roles
 *  - Per User : override menu per user (beri/cabut)  → ms_sales_{type}_menu_users
 *  - Preview  : simulasi sidebar untuk role tertentu (dihitung di browser, algoritma sama dengan RbacEngine)
 *
 * Hanya menu aktif yang ditampilkan & diubah; override pada menu nonaktif tidak disentuh.
 * Akun user dibaca dari database WRS Sales (read only).
 */
class SalesMenuAccessController extends AfselAccessController
{
    use SalesAccessContext;

    public function index(Request $request)
    {
        $type  = $this->type($request);
        $menus = $this->activeMenuTree($type);
        $activeIds = array_column($menus, 'id');

        $roleMenus = DB::table($this->table($type, 'menu_roles'))->whereIn('menu_id', $activeIds)->get(['role_id', 'menu_id'])
            ->groupBy('role_id')->map(fn ($rows) => $rows->pluck('menu_id')->map(fn ($v) => (int) $v)->values()->all());

        $rolePerms = DB::table($this->table($type, 'role_permissions') . ' as rp')
            ->join($this->table($type, 'permissions') . ' as p', 'p.id', '=', 'rp.permission_id')
            ->get(['rp.role_id', 'p.slug'])
            ->groupBy('role_id')->map(fn ($rows) => $rows->pluck('slug')->values()->all());

        $userCounts = DB::table($this->table($type, 'user_roles'))->select('role_id', DB::raw('count(*) as total'))
            ->groupBy('role_id')->pluck('total', 'role_id');

        $roles = DB::table($this->table($type, 'roles'))->orderBy('level')->orderBy('name')->get()
            ->map(function ($role) use ($type, $roleMenus, $rolePerms, $userCounts) {
                return [
                    'id'          => (int) $role->id,
                    'name'        => $role->name,
                    'slug'        => $role->slug,
                    'description' => $role->description,
                    'level'       => (int) $role->level,
                    'is_system'   => (bool) $role->is_system,
                    'is_active'   => (bool) $role->is_active,
                    'can_manage'  => $this->rbac->canManageRole($type, $role, $this->actor()),
                    'menu_ids'    => $roleMenus[$role->id] ?? [],
                    'perm_slugs'  => $rolePerms[$role->id] ?? [],
                    'users_count' => (int) ($userCounts[$role->id] ?? 0),
                ];
            })->values();

        return view('sales.atpm.page_access.menu_access', $this->viewData($type, [
            'menus' => $menus,
            'roles' => $roles,
        ]));
    }

    /** Tab Per Role: simpan menu role (replace). Parent menu yang dicentang child-nya ikut disimpan. */
    public function syncRole(Request $request)
    {
        $type = $this->type($request);

        $validator = Validator::make($request->all(), [
            'role_id'    => ['required', 'integer', Rule::exists($this->table($type, 'roles'), 'id')],
            'menu_ids'   => ['nullable', 'array'],
            'menu_ids.*' => ['integer'],
        ]);
        if ($validator->fails()) {
            return $this->fail('Data role / menu tidak valid.');
        }

        $role = $this->findOrFail($type, 'roles', (int) $request->input('role_id'));

        if ($role->is_system) {
            return $this->fail('Role Super Admin otomatis mendapat semua menu aktif, tidak perlu diatur.', 403);
        }
        if (!$this->rbac->canManageRole($type, $role, $this->actor())) {
            return $this->fail('Anda tidak memiliki hak untuk mengubah akses role ini.', 403);
        }

        $activeIds = array_column($this->activeMenuTree($type), 'id');
        $selected  = array_values(array_intersect(
            $this->rbac->withMenuAncestors($type, array_map('intval', $request->input('menu_ids', []))),
            $activeIds
        ));

        // Menu nonaktif yang sudah tercatat di role dipertahankan (tidak tampil di layar ini)
        $keepInactive = DB::table($this->table($type, 'menu_roles'))->where('role_id', $role->id)
            ->whereNotIn('menu_id', $activeIds)->pluck('menu_id')->map(fn ($v) => (int) $v)->all();

        $this->rbac->syncRoleMenus($type, (int) $role->id, array_merge($selected, $keepInactive), $this->actor());

        return $this->ok("Menu untuk role \"{$role->name}\" berhasil disimpan.", ['menu_ids' => $selected]);
    }

    /** Tab Per User: cari user di database WRS Sales. */
    public function users(Request $request)
    {
        $type = $this->type($request);
        $cfg  = $this->rbac->config($type);
        $key  = $cfg['user_key'];
        $db   = $this->rbac->userDb();
        $like = $db->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $q    = trim((string) $request->input('q', ''));

        $query = $db->table($cfg['user_table'] . ' as u')
            ->select(["u.{$key} as kd_user", 'u.username', "u.{$cfg['user_name']} as name", 'u.is_active']);

        if ($type === 'dealer') {
            $query->addSelect('d.nm_dealer')->leftJoin('tbldealer as d', 'd.kd_dealer', '=', 'u.fk_dealer');
        }

        if ($q !== '') {
            $kw = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $query->where(function ($w) use ($cfg, $like, $kw, $type) {
                $w->where('u.username', $like, $kw)->orWhere("u.{$cfg['user_name']}", $like, $kw);
                if ($type === 'dealer') {
                    $w->orWhere('d.nm_dealer', $like, $kw);
                }
            });
        } else {
            // Tanpa kata kunci: tampilkan user yang sudah punya role Sales lebih dulu
            $withRole = DB::table($this->table($type, 'user_roles'))->distinct()->limit(500)->pluck($key)->map(fn ($v) => (string) $v)->all();
            if ($withRole) {
                $query->whereIn("u.{$key}", $withRole);
            }
        }

        $rows = $query->orderBy("u.{$cfg['user_name']}")->limit(30)->get();

        return response()->json([
            'status' => true,
            'data'   => $rows->map(fn ($r) => [
                'kd_user'   => (string) $r->kd_user,
                'username'  => $r->username,
                'name'      => $r->name,
                'is_active' => filter_var($r->is_active, FILTER_VALIDATE_BOOLEAN),
                'dealer'    => $r->nm_dealer ?? null,
            ])->values(),
        ]);
    }

    /** Tab Per User: role user, menu dari role (sebelum override), dan override yang tersimpan. */
    public function userAccess(Request $request, string $kdUser)
    {
        $type = $this->type($request);
        $cfg  = $this->rbac->config($type);
        $key  = $cfg['user_key'];

        $user = $this->rbac->userDb()->table($cfg['user_table'])->where($key, $kdUser)
            ->first([$key, 'username', $cfg['user_name'] . ' as name', 'is_active']);
        if (!$user) {
            return $this->fail('User tidak ditemukan di database WRS Sales.', 404);
        }

        $roles = $this->rbac->getAssignedRoles($type, $kdUser);
        $activeIds = array_column($this->activeMenuTree($type), 'id');

        // menu_id => nama role yang memberi akses
        $roleMenus = [];
        DB::table($this->table($type, 'menu_roles'))->whereIn('role_id', $roles->pluck('id'))->whereIn('menu_id', $activeIds)
            ->get(['menu_id', 'role_id'])
            ->each(function ($row) use (&$roleMenus, $roles) {
                $roleMenus[(int) $row->menu_id][] = $roles->firstWhere('id', $row->role_id)?->name;
            });

        $overrides = DB::table($this->table($type, 'menu_users'))->where($key, $kdUser)->whereIn('menu_id', $activeIds)
            ->pluck('is_granted', 'menu_id')->map(fn ($g) => $g ? 'grant' : 'revoke');

        return response()->json([
            'status'     => true,
            'user'       => [
                'kd_user'   => (string) $kdUser,
                'username'  => $user->username,
                'name'      => $user->name,
                'is_active' => filter_var($user->is_active, FILTER_VALIDATE_BOOLEAN),
                'roles'     => $roles->map(fn ($r) => ['id' => (int) $r->id, 'name' => $r->name, 'is_system' => (bool) $r->is_system])->values(),
            ],
            'role_menus' => (object) $roleMenus,
            'overrides'  => (object) $overrides->all(),
        ]);
    }

    /**
     * Tab Per User: simpan override. `overrides` = {menu_id: grant|revoke} untuk SELURUH override yang diinginkan;
     * menu aktif yang tidak ada di daftar kembali mengikuti role.
     */
    public function syncUser(Request $request, string $kdUser)
    {
        $type = $this->type($request);
        $cfg  = $this->rbac->config($type);

        if (!$this->rbac->userDb()->table($cfg['user_table'])->where($cfg['user_key'], $kdUser)->exists()) {
            return $this->fail('User tidak ditemukan di database WRS Sales.', 404);
        }

        $validator = Validator::make($request->all(), [
            'overrides'   => ['nullable', 'array'],
            'overrides.*' => ['in:grant,revoke'],
        ]);
        if ($validator->fails()) {
            return $this->fail('Data override menu tidak valid.');
        }

        $wanted = collect($request->input('overrides', []))->mapWithKeys(fn ($mode, $menuId) => [(int) $menuId => $mode]);
        $map = [];
        foreach (array_column($this->activeMenuTree($type), 'id') as $menuId) {
            $map[$menuId] = $wanted[$menuId] ?? 'default';
        }

        $this->rbac->syncUserMenuOverrides($type, $kdUser, $map, $this->actor());

        $count = count(array_filter($map, fn ($m) => $m !== 'default'));

        return $this->ok($count
            ? "Override menu disimpan ({$count} menu berbeda dari role)."
            : 'Override dihapus, menu user kembali mengikuti role.');
    }

    /**
     * Menu aktif dalam urutan tree + depth. Menu nonaktif beserta seluruh turunannya tidak ikut
     * (sama seperti sidebar: child tidak tampil jika parent tidak tampil).
     */
    private function activeMenuTree(string $type): array
    {
        $result = [];
        $skipBelow = null;

        foreach ($this->menuTreeFlat($type) as $menu) {
            if ($skipBelow !== null && $menu->depth > $skipBelow) {
                continue;
            }
            $skipBelow = null;

            if (!$menu->is_active) {
                $skipBelow = $menu->depth;
                continue;
            }

            $result[] = [
                'id'              => (int) $menu->id,
                'parent_id'       => $menu->parent_id ? (int) $menu->parent_id : null,
                'depth'           => (int) $menu->depth,
                'label'           => $menu->label,
                'name'            => $menu->name,
                'icon'            => $menu->icon,
                'route_name'      => $menu->route_name,
                'url'             => $menu->url,
                'permission_slug' => $menu->permission_slug,
            ];
        }

        return $result;
    }
}

<?php

namespace App\Http\Controllers\Afsel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Master Role + pengaturan Permission & Menu per role.
 */
class AfselRoleController extends AfselAccessController
{
    public function index(Request $request)
    {
        $type = $this->type($request);
        $r  = $this->table($type, 'roles');

        $roles = DB::table($r)
            ->select("{$r}.*")
            ->selectSub(fn ($q) => $q->from($this->table($type, 'user_roles'))->selectRaw('count(*)')->whereColumn('role_id', "{$r}.id"), 'users_count')
            ->selectSub(fn ($q) => $q->from($this->table($type, 'role_permissions'))->selectRaw('count(*)')->whereColumn('role_id', "{$r}.id"), 'permissions_count')
            ->selectSub(fn ($q) => $q->from($this->table($type, 'menu_roles'))->selectRaw('count(*)')->whereColumn('role_id', "{$r}.id"), 'menus_count')
            ->orderBy('level')->orderBy('name')
            ->get()
            ->each(function ($role) use ($type) {
                // Catatan: jangan pakai arrow fn — each() berhenti jika callback mengembalikan false
                $role->can_manage = $this->rbac->canManageRole($type, $role, $this->actor());
            });

        return view('aftersales.atpm.page_access.roles', $this->viewData($type, ['roles' => $roles]));
    }

    public function store(Request $request)
    {
        $type = $this->type($request);
        $table = $this->table($type, 'roles');

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:100', Rule::unique($table, 'name')],
            'slug'        => ['required', 'string', 'max:100', 'regex:' . self::SLUG_REGEX, Rule::unique($table, 'slug')],
            'description' => ['nullable', 'string', 'max:500'],
            'level'       => ['required', 'integer', 'min:1', 'max:999'],
            'is_active'   => ['boolean'],
            'is_system'   => ['boolean'],
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $role = (object) ['is_system' => (bool) ($data['is_system'] ?? false), 'level' => (int) $data['level']];

        if (!$this->rbac->canManageRole($type, $role, $this->actor())) {
            return $this->fail('Anda tidak boleh membuat role dengan privilege lebih tinggi dari role Anda.', 403);
        }

        DB::table($table)->insert([
            'name'         => $data['name'],
            'slug'         => $data['slug'],
            'description'  => $data['description'] ?? null,
            'level'        => $data['level'],
            'is_active'    => $data['is_active'] ?? true,
            'is_system'    => $role->is_system,
            'created_by'   => $this->actor(),
            'date_created' => now(),
        ]);

        $this->rbac->flushCache($type);

        return $this->ok('Role berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $type  = $this->type($request);
        $table = $this->table($type, 'roles');
        $role  = $this->findOrFail($type, 'roles', $id);

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:100', Rule::unique($table, 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
            'level'       => ['required', 'integer', 'min:1', 'max:999'],
            'is_active'   => ['boolean'],
            'is_system'   => ['boolean'],
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $new  = (object) ['is_system' => (bool) ($data['is_system'] ?? $role->is_system), 'level' => (int) $data['level']];

        if (!$this->rbac->canManageRole($type, $role, $this->actor()) || !$this->rbac->canManageRole($type, $new, $this->actor())) {
            return $this->fail('Anda tidak memiliki hak untuk mengubah role ini.', 403);
        }

        DB::table($table)->where('id', $id)->update([
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'level'        => $data['level'],
            'is_active'    => $data['is_active'] ?? $role->is_active,
            'is_system'    => $new->is_system,
            'updated_by'   => $this->actor(),
            'date_updated' => now(),
        ]);

        $this->rbac->flushCache($type);

        return $this->ok('Role berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id)
    {
        $type = $this->type($request);
        $role = $this->findOrFail($type, 'roles', $id);

        if (!$this->rbac->canManageRole($type, $role, $this->actor())) {
            return $this->fail('Anda tidak memiliki hak untuk menghapus role ini.', 403);
        }

        $users = DB::table($this->table($type, 'user_roles'))->where('role_id', $id)->count();
        if ($users > 0) {
            return $this->fail("Role masih dipakai oleh {$users} user. Lepaskan role dari user terlebih dahulu.");
        }

        DB::transaction(function () use ($type, $id) {
            DB::table($this->table($type, 'role_permissions'))->where('role_id', $id)->delete();
            DB::table($this->table($type, 'menu_roles'))->where('role_id', $id)->delete();
            DB::table($this->table($type, 'roles'))->where('id', $id)->delete();
        });

        $this->rbac->flushCache($type);

        return $this->ok('Role berhasil dihapus.');
    }

    /**
     * Halaman atur Permission & Menu untuk satu role.
     */
    public function access(Request $request, int $id)
    {
        $type = $this->type($request);
        $role = $this->findOrFail($type, 'roles', $id);

        $permissions = DB::table($this->table($type, 'permissions'))
            ->orderBy('group_name')->orderBy('name')->get()
            ->groupBy(fn ($p) => $p->group_name ?: 'Lainnya');

        return view('aftersales.atpm.page_access.role_access', $this->viewData($type, [
            'role'                => $role,
            'canManage'           => $this->rbac->canManageRole($type, $role, $this->actor()),
            'permissions'         => $permissions,
            'menus'               => $this->menuTreeFlat($type),
            'rolePermissionIds'   => DB::table($this->table($type, 'role_permissions'))->where('role_id', $id)->pluck('permission_id')->map(fn ($v) => (int) $v)->all(),
            'roleMenuIds'         => DB::table($this->table($type, 'menu_roles'))->where('role_id', $id)->pluck('menu_id')->map(fn ($v) => (int) $v)->all(),
        ]));
    }

    public function saveAccess(Request $request, int $id)
    {
        $type = $this->type($request);
        $role = $this->findOrFail($type, 'roles', $id);

        if (!$this->rbac->canManageRole($type, $role, $this->actor())) {
            return $this->fail('Anda tidak memiliki hak untuk mengubah akses role ini.', 403);
        }

        $validator = Validator::make($request->all(), [
            'permission_ids'   => ['array'],
            'permission_ids.*' => ['integer', Rule::exists($this->table($type, 'permissions'), 'id')],
            'menu_ids'         => ['array'],
            'menu_ids.*'       => ['integer', Rule::exists($this->table($type, 'menus'), 'id')],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data permission / menu tidak valid.');
        }

        $menuIds = $this->rbac->withMenuAncestors($type, $request->input('menu_ids', []));

        DB::transaction(function () use ($type, $id, $request, $menuIds) {
            $this->rbac->syncRolePermissions($type, $id, $request->input('permission_ids', []), $this->actor());
            $this->rbac->syncRoleMenus($type, $id, $menuIds, $this->actor());
        });

        return $this->ok('Akses role berhasil disimpan.');
    }

    private function messages(): array
    {
        return [
            'name.required'  => 'Nama role wajib diisi.',
            'name.unique'    => 'Nama role sudah dipakai.',
            'slug.required'  => 'Slug wajib diisi.',
            'slug.regex'     => 'Slug hanya boleh huruf kecil, angka, titik dan strip (contoh: service-advisor).',
            'slug.unique'    => 'Slug sudah dipakai.',
            'level.required' => 'Level wajib diisi.',
        ];
    }
}

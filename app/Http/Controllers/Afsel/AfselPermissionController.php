<?php

namespace App\Http\Controllers\Afsel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Master Permission.
 * Slug tidak bisa diubah setelah dibuat karena dipakai di kode (middleware afsel.permission / @afselcan).
 */
class AfselPermissionController extends AfselAccessController
{
    public function index(Request $request)
    {
        $type = $this->type($request);
        $p = $this->table($type, 'permissions');

        $permissions = DB::table($p)
            ->select("{$p}.*")
            ->selectSub(fn ($q) => $q->from($this->table($type, 'role_permissions'))->selectRaw('count(*)')->whereColumn('permission_id', "{$p}.id"), 'roles_count')
            ->orderBy('group_name')->orderBy('name')
            ->get();

        $routeSlugs = $this->slugsUsedInRoutes($type);
        $menuSlugs  = DB::table($this->table($type, 'menus'))->whereNotNull('permission_slug')->pluck('permission_slug')->unique()->all();

        foreach ($permissions as $perm) {
            $perm->used_in_routes = $routeSlugs[$perm->slug] ?? [];
            $perm->used_in_menu   = in_array($perm->slug, $menuSlugs, true);
        }

        return view('aftersales.atpm.page_access.permissions', $this->viewData($type, [
            'permissions' => $permissions,
            'groups'      => $permissions->pluck('group_name')->filter()->unique()->values(),
            // Slug yang dipakai di route tapi belum ada di tabel permission
            'missingSlugs' => array_values(array_diff(array_keys($routeSlugs), $permissions->pluck('slug')->all())),
        ]));
    }

    public function store(Request $request)
    {
        $type  = $this->type($request);
        $table = $this->table($type, 'permissions');

        $validator = Validator::make($request->all(), [
            'slug'        => ['required', 'string', 'max:100', 'regex:' . self::SLUG_REGEX, Rule::unique($table, 'slug')],
            'name'        => ['required', 'string', 'max:100'],
            'group_name'  => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        DB::table($table)->insert($validator->validated() + ['created_by' => $this->actor(), 'date_created' => now()]);
        $this->rbac->flushCache($type);

        return $this->ok('Permission berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $type = $this->type($request);
        $this->findOrFail($type, 'permissions', $id);

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:100'],
            'group_name'  => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        DB::table($this->table($type, 'permissions'))->where('id', $id)
            ->update($validator->validated() + ['updated_by' => $this->actor(), 'date_updated' => now()]);
        $this->rbac->flushCache($type);

        return $this->ok('Permission berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id)
    {
        $type = $this->type($request);
        $perm = $this->findOrFail($type, 'permissions', $id);

        if (!empty($this->slugsUsedInRoutes($type)[$perm->slug])) {
            return $this->fail("Permission '{$perm->slug}' masih dipakai di route aplikasi, tidak bisa dihapus.");
        }

        if (DB::table($this->table($type, 'menus'))->where('permission_slug', $perm->slug)->exists()) {
            return $this->fail("Permission '{$perm->slug}' masih dipakai oleh menu. Kosongkan dulu permission di menu tersebut.");
        }

        DB::transaction(function () use ($type, $id) {
            DB::table($this->table($type, 'role_permissions'))->where('permission_id', $id)->delete();
            DB::table($this->table($type, 'permissions'))->where('id', $id)->delete();
        });

        $this->rbac->flushCache($type);

        return $this->ok('Permission berhasil dihapus.');
    }

    /** Nama middleware RBAC untuk deteksi slug di route (dioverride untuk Sales). */
    protected function middlewareNames(): array
    {
        return ['auth' => 'afsel.auth', 'permission' => 'afsel.permission'];
    }

    /**
     * Slug permission yang dipakai middleware permission di route tipe ini.
     * @return array<string, string[]> slug => [route name...]
     */
    private function slugsUsedInRoutes(string $type): array
    {
        $result = [];

        foreach (Route::getRoutes() as $route) {
            $middleware = $route->gatherMiddleware();

            if (!in_array("afsel.auth:{$type}", $middleware, true)) {
                continue;
            }

            foreach ($middleware as $m) {
                if (is_string($m) && str_starts_with($m, 'afsel.permission:')) {
                    foreach (explode(',', substr($m, strlen('afsel.permission:'))) as $slug) {
                        $result[$slug][] = $route->getName() ?? $route->uri();
                    }
                }
            }
        }

        return $result;
    }

    private function messages(): array
    {
        return [
            'slug.required' => 'Slug wajib diisi.',
            'slug.regex'    => 'Slug hanya boleh huruf kecil, angka, titik dan strip (contoh: report-retention.view).',
            'slug.unique'   => 'Slug sudah dipakai.',
            'name.required' => 'Nama permission wajib diisi.',
        ];
    }
}

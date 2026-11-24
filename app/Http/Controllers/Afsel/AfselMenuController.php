<?php

namespace App\Http\Controllers\Afsel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Master Menu sidebar (hierarki parent_id, kedalaman bebas).
 */
class AfselMenuController extends AfselAccessController
{
    /** Prefix nama route yang boleh dipakai menu per tipe (dioverride untuk Sales). */
    protected function routePrefixes(string $type): array
    {
        return [
            'atpm'   => ['aftersales.atpm.'],
            'dealer' => ['dealer.', 'aftersales.dealer.'],
        ][$type] ?? [];
    }

    public function index(Request $request)
    {
        $type = $this->type($request);

        return view('aftersales.atpm.page_access.menus', $this->viewData($type, [
            'menus'       => $this->menuTreeFlat($type),
            'routes'      => $this->routes($type),
            'permissions' => DB::table($this->table($type, 'permissions'))->orderBy('group_name')->orderBy('name')->get(['slug', 'name']),
        ]));
    }

    public function store(Request $request)
    {
        $type = $this->type($request);
        $validator = $this->validator($request, $type);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        DB::table($this->table($type, 'menus'))->insert(
            $this->payload($validator->validated()) + ['created_by' => $this->actor(), 'date_created' => now()]
        );
        $this->rbac->flushCache($type);

        return $this->ok('Menu berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $type = $this->type($request);
        $this->findOrFail($type, 'menus', $id);

        $validator = $this->validator($request, $type, $id);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();

        if (!empty($data['parent_id']) && in_array((int) $data['parent_id'], $this->descendantIds($type, $id, true), true)) {
            return $this->fail('Parent tidak boleh menu itu sendiri atau turunannya.');
        }

        DB::table($this->table($type, 'menus'))->where('id', $id)
            ->update($this->payload($data) + ['updated_by' => $this->actor(), 'date_updated' => now()]);
        $this->rbac->flushCache($type);

        return $this->ok('Menu berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id)
    {
        $type = $this->type($request);
        $this->findOrFail($type, 'menus', $id);

        if (DB::table($this->table($type, 'menus'))->where('parent_id', $id)->exists()) {
            return $this->fail('Menu masih memiliki sub menu. Hapus atau pindahkan sub menu terlebih dahulu.');
        }

        DB::transaction(function () use ($type, $id) {
            DB::table($this->table($type, 'menu_roles'))->where('menu_id', $id)->delete();
            DB::table($this->table($type, 'menu_users'))->where('menu_id', $id)->delete();
            DB::table($this->table($type, 'menus'))->where('id', $id)->delete();
        });

        $this->rbac->flushCache($type);

        return $this->ok('Menu berhasil dihapus.');
    }

    private function validator(Request $request, string $type, ?int $id = null)
    {
        $table = $this->table($type, 'menus');

        return Validator::make($request->all(), [
            'parent_id'       => ['nullable', 'integer', Rule::exists($table, 'id')],
            'name'            => ['required', 'string', 'max:100', 'regex:' . self::SLUG_REGEX, Rule::unique($table, 'name')->ignore($id)],
            'label'           => ['required', 'string', 'max:255'],
            'route_name'      => ['nullable', 'string', 'max:255', Rule::in(array_keys($this->routes($type)))],
            'url'             => ['nullable', 'string', 'max:500', 'regex:/^(\/|https?:\/\/)/'],
            'icon'            => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9 \-]+$/'],
            'sort_order'      => ['required', 'integer', 'min:0', 'max:9999'],
            'permission_slug' => ['nullable', 'string', Rule::exists($this->table($type, 'permissions'), 'slug')],
            'is_active'       => ['boolean'],
        ], [
            'name.required'      => 'Kode menu wajib diisi.',
            'name.regex'         => 'Kode menu hanya boleh huruf kecil, angka, titik dan strip (contoh: reports.retention).',
            'name.unique'        => 'Kode menu sudah dipakai.',
            'label.required'     => 'Label menu wajib diisi.',
            'route_name.in'      => 'Route ":input" tidak dikenal untuk area ' . strtoupper($type) . '. Pilih dari saran yang muncul saat mengetik.',
            'url.regex'          => 'URL harus diawali "/" atau "http(s)://".',
            'icon.regex'         => 'Icon hanya class Bootstrap Icons, contoh: bi bi-house-fill.',
            'permission_slug.exists' => 'Permission tidak ditemukan.',
        ]);
    }

    private function payload(array $data): array
    {
        return [
            'parent_id'       => $data['parent_id'] ?? null,
            'name'            => $data['name'],
            'label'           => $data['label'],
            'route_name'      => $data['route_name'] ?? null,
            'url'             => $data['url'] ?? null,
            'icon'            => $data['icon'] ?? null,
            'sort_order'      => $data['sort_order'],
            'permission_slug' => $data['permission_slug'] ?? null,
            'is_active'       => $data['is_active'] ?? true,
        ];
    }

    /**
     * Route GET yang valid untuk menu tipe ini: [nama route => URL path].
     * Tanpa parameter & bukan endpoint datatable.
     */
    private function routes(string $type): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (!$name || !in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{')) {
                continue;
            }
            foreach ($this->routePrefixes($type) as $prefix) {
                if (str_starts_with($name, $prefix) && !str_contains($name, 'datatable')) {
                    $routes[$name] = '/' . ltrim($route->uri(), '/');
                }
            }
        }

        ksort($routes);

        return $routes;
    }

    private function descendantIds(string $type, int $id, bool $includeSelf = false): array
    {
        $children = DB::table($this->table($type, 'menus'))->get(['id', 'parent_id'])->groupBy(fn ($m) => (int) $m->parent_id);
        $result = $includeSelf ? [$id] : [];
        $stack = [$id];

        while ($stack) {
            foreach ($children[array_pop($stack)] ?? [] as $child) {
                $result[] = (int) $child->id;
                $stack[] = (int) $child->id;
            }
        }

        return $result;
    }
}

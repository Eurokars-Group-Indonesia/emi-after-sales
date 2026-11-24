<?php

namespace App\Http\Controllers\Afsel;

use App\Http\Controllers\Controller;
use App\Services\Afsel\AfselRbacService;
use App\Services\Rbac\RbacEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Base controller halaman admin RBAC (Access Control).
 * Halaman berada di area ATPM, tapi bisa mengelola RBAC ATPM maupun Dealer (parameter ?type=atpm|dealer).
 *
 * Dipakai oleh After Sales (AfselRbacService, default di class ini) dan Sales
 * (App\Http\Controllers\Sales\Access\*, lewat trait SalesAccessContext yang meng-override
 * service RBAC, layout, prefix route, dan pengecekan permission). View page_access dipakai bersama
 * dan membaca konteks dari variabel $acx.
 */
abstract class AfselAccessController extends Controller
{
    /** Format slug permission / role / kode menu: huruf kecil, angka, titik, strip. */
    public const SLUG_REGEX = '/^[a-z0-9]+([.\-][a-z0-9]+)*$/';

    /** @var RbacEngine AfselRbacService (After Sales) / SalesRbacService (Sales) */
    protected RbacEngine $rbac;

    public function __construct(AfselRbacService $rbac)
    {
        $this->rbac = $rbac;
    }

    /**
     * Konteks tampilan & route halaman Access Control (dioverride untuk Sales).
     *  layout/navtop/sidebar : view layout area pengelola
     *  route                 : prefix nama route halaman access (… .roles, … .users, dst.)
     *  home                  : route home area pengelola
     *  app                   : label aplikasi
     *  perm_middleware/perm_directive : nama middleware & Blade directive permission (untuk teks bantuan)
     */
    protected function context(): array
    {
        return [
            'layout'          => 'aftersales.atpm.layouts.app',
            'navtop'          => 'aftersales.atpm.layouts.navtop',
            'sidebar'         => 'aftersales.atpm.layouts.sidebar',
            'route'           => 'aftersales.atpm.access.',
            'home'            => 'aftersales.atpm.home',
            'app'             => 'After Sales',
            'perm_middleware' => 'afsel.permission',
            'perm_directive'  => 'afselcan',
        ];
    }

    /** Cek permission user pengelola yang sedang login. */
    protected function can(string $slug): bool
    {
        return afsel_can($slug);
    }

    protected function type(Request $request): string
    {
        $type = (string) $request->input('type', 'atpm');
        // Hanya tipe milik service RBAC controller ini (After Sales / Sales tidak bisa saling diakses)
        abort_unless($this->rbac->hasType($type), 404);

        return $type;
    }

    protected function table(string $type, string $name): string
    {
        return $this->rbac->table($type, $name);
    }

    /** kd_atpm_user pengelola yang sedang login. */
    protected function actor(): string
    {
        return (string) session('user.id');
    }

    protected function isSuperAdmin(): bool
    {
        return $this->rbac->isSuperAdmin($this->rbac::ADMIN_TYPE, $this->actor());
    }

    protected function findOrFail(string $type, string $name, int $id): object
    {
        $row = DB::table($this->table($type, $name))->where('id', $id)->first();
        abort_unless($row, 404);

        return $row;
    }

    protected function ok(string $message, array $extra = []): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $message, 'errors' => ''] + $extra);
    }

    protected function fail(string $message, int $code = 422, array $errors = []): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message, 'errors' => $errors], $code);
    }

    /** Data umum untuk semua view halaman akses. */
    protected function viewData(string $type, array $data = []): array
    {
        return $data + [
            'type'      => $type,
            'typeLabel' => strtoupper($type),
            'isSuper'   => $this->isSuperAdmin(),
            'acx'       => $this->context(),
        ];
    }

    /**
     * Menu dalam urutan tree (flat) + depth, untuk tabel / checkbox tree.
     */
    protected function menuTreeFlat(string $type): array
    {
        $menus = DB::table($this->table($type, 'menus'))->orderBy('sort_order')->orderBy('label')->get();
        $byParent = $menus->groupBy(fn ($m) => (int) $m->parent_id);
        $result = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$result, $byParent) {
            foreach ($byParent[$parentId] ?? [] as $menu) {
                $menu->depth = $depth;
                $result[] = $menu;
                $walk((int) $menu->id, $depth + 1);
            }
        };
        $walk(0, 0);

        // Menu yatim (parent tidak ada) tetap ditampilkan di akhir
        $seen = array_column($result, 'id');
        foreach ($menus as $menu) {
            if (!in_array($menu->id, $seen)) {
                $menu->depth = 0;
                $result[] = $menu;
            }
        }

        return $result;
    }
}

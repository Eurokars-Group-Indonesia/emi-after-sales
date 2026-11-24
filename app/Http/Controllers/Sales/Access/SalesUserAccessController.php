<?php

namespace App\Http\Controllers\Sales\Access;

use App\Http\Controllers\Afsel\AfselUserAccessController;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Access Control Sales: daftar user Sales (ATPM / Dealer) + atur role & override menu per user.
 *
 * Akun user milik database WRS Sales (tblatpm_user / tbldealer_user, PostgreSQL) dan hanya dibaca di sini;
 * tambah / ubah data user dilakukan di WRS Sales. Role & menu disimpan di tabel RBAC Sales lokal.
 * Karena beda database (tidak bisa JOIN), datatable dibuat manual: data user dari WRS Sales,
 * nama role dilengkapi dari tabel lokal untuk baris yang tampil saja.
 */
class SalesUserAccessController extends AfselUserAccessController
{
    use SalesAccessContext;

    public function index(Request $request)
    {
        $type = $this->type($request);
        $cfg  = $this->rbac->config($type);

        $total    = $this->users($type)->count();
        $active   = $this->users($type)->where('u.is_active', true)->count();
        $withRole = $this->countWithRole($type, $this->userKeysWithRole($type));

        $stats = (object) [
            'total'    => $total,
            'active'   => $active,
            'inactive' => $total - $active,
            'no_role'  => max(0, $total - $withRole),
        ];

        return view('aftersales.atpm.page_access.users', $this->viewData($type, [
            'stats'          => $stats,
            'dealers'        => $type === 'dealer'
                ? $this->rbac->userDb()->table('tbldealer')->where('is_active', true)->orderBy('nm_dealer')->get(['kd_dealer', 'nm_dealer'])
                : collect(),
            'roles'          => collect(),
            'canManageUser'  => false, // akun dikelola di WRS Sales
            'showLastLogin'  => false,
            'userSourceNote' => 'Akun user diambil dari database WRS Sales (' . $cfg['user_table'] . '). Tambah / ubah data user dilakukan di WRS Sales; di sini hanya mengatur role & menu.',
        ]));
    }

    public function datatable(Request $request)
    {
        $type = $this->type($request);
        $cfg  = $this->rbac->config($type);
        $key  = $cfg['user_key'];

        $query = $this->users($type)->select([
            "u.{$key} as kd_user", 'u.username', "u.{$cfg['user_name']} as name", 'u.email', 'u.is_active',
        ]);

        if ($type === 'dealer') {
            $query->addSelect(['u.fk_dealer', 'd.nm_dealer'])->leftJoin('tbldealer as d', 'd.kd_dealer', '=', 'u.fk_dealer');
        }

        $recordsTotal = (clone $query)->count();

        // ---- filter ----
        if ($type === 'dealer' && ($dealer = $request->input('fk_dealer'))) {
            $query->where('u.fk_dealer', $dealer);
        }

        switch ($request->input('status')) {
            case 'active':   $query->where('u.is_active', true); break;
            case 'inactive': $query->where('u.is_active', false); break;
        }

        if ($request->input('no_role') === '1') {
            foreach (array_chunk($this->userKeysWithRole($type), 1000) as $chunk) {
                $query->whereNotIn("u.{$key}", $chunk);
            }
        }

        $search = trim((string) data_get($request->input('search'), 'value', ''));
        if ($search !== '') {
            $like = $this->likeOperator();
            $kw   = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $query->where(function ($q) use ($cfg, $like, $kw, $type) {
                $q->where('u.username', $like, $kw)
                    ->orWhere("u.{$cfg['user_name']}", $like, $kw)
                    ->orWhere('u.email', $like, $kw);
                if ($type === 'dealer') {
                    $q->orWhere('d.nm_dealer', $like, $kw);
                }
            });
        }

        $recordsFiltered = (clone $query)->count();

        // ---- urutan (kolom whitelist) ----
        $sortable = ['name' => "u.{$cfg['user_name']}", 'u.email' => 'u.email', 'd.nm_dealer' => 'd.nm_dealer', 'u.is_active' => 'u.is_active'];
        foreach ((array) $request->input('order', []) as $order) {
            $colName = data_get($request->input('columns'), ((int) ($order['column'] ?? -1)) . '.name');
            if (isset($sortable[$colName])) {
                $query->orderBy($sortable[$colName], ($order['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc');
            }
        }
        $query->orderBy("u.{$key}");

        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 10 : min($length, 100);
        $rows   = $query->offset(max(0, (int) $request->input('start', 0)))->limit($length)->get();

        $roleNames = $this->roleNamesFor($type, $rows->pluck('kd_user')->map(fn ($v) => (string) $v)->all());

        $data = $rows->map(fn ($row) => [
            'kd_user'       => (string) $row->kd_user,
            'username'      => $row->username,
            'name'          => $row->name,
            'email'         => $row->email,
            'is_active'     => filter_var($row->is_active, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'fk_dealer'     => $row->fk_dealer ?? null,
            'nm_dealer'     => $row->nm_dealer ?? null,
            'role_names'    => $roleNames[(string) $row->kd_user] ?? null,
            'last_login_at' => null,
            'is_local'      => false,
            'access_url'    => route($this->context()['route'] . 'users.edit', ['kdUser' => (string) $row->kd_user, 'type' => $type]),
        ])->values();

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function edit(Request $request, string $kdUser)
    {
        $response = parent::edit($request, $kdUser);

        return $response->with('showLastLogin', false);
    }

    /** User dari database WRS Sales. */
    protected function findUser(string $type, string $kdUser): ?object
    {
        $cfg  = $this->rbac->config($type);
        $user = $this->users($type)->where("u.{$cfg['user_key']}", $kdUser)->first();

        if ($user) {
            $user->is_active     = filter_var($user->is_active, FILTER_VALIDATE_BOOLEAN);
            $user->last_login_at = null;
            unset($user->password);
        }

        return $user;
    }

    private function users(string $type): Builder
    {
        return $this->rbac->userDb()->table($this->rbac->config($type)['user_table'] . ' as u');
    }

    private function likeOperator(): string
    {
        return $this->rbac->userDb()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /** Kode user yang punya baris role di RBAC Sales lokal. */
    private function userKeysWithRole(string $type): array
    {
        $key = $this->rbac->config($type)['user_key'];

        return DB::table($this->table($type, 'user_roles'))->distinct()->pluck($key)->map(fn ($v) => (string) $v)->all();
    }

    private function countWithRole(string $type, array $keys): int
    {
        $key = $this->rbac->config($type)['user_key'];
        $count = 0;

        foreach (array_chunk($keys, 1000) as $chunk) {
            $count += $this->users($type)->whereIn("u.{$key}", $chunk)->count();
        }

        return $count;
    }

    /** [kd_user => 'Role A||Role B'] untuk role yang belum kedaluwarsa, urut level. */
    private function roleNamesFor(string $type, array $userKeys): array
    {
        if (empty($userKeys)) {
            return [];
        }

        $key = $this->rbac->config($type)['user_key'];

        return DB::table($this->table($type, 'user_roles') . ' as ur')
            ->join($this->table($type, 'roles') . ' as r', 'r.id', '=', 'ur.role_id')
            ->whereIn("ur.{$key}", $userKeys)
            ->where(fn ($w) => $w->whereNull('ur.expires_at')->orWhere('ur.expires_at', '>', now()))
            ->orderBy('r.level')->orderBy('r.name')
            ->get(["ur.{$key} as kd_user", 'r.name'])
            ->groupBy(fn ($r) => (string) $r->kd_user)
            ->map(fn ($rows) => $rows->pluck('name')->implode('||'))
            ->all();
    }
}

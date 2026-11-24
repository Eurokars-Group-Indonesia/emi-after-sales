<?php

namespace App\Http\Controllers\Afsel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/**
 * User After Sales (ATPM & Dealer): daftar, tambah / ubah user di luar sync,
 * serta akses per user: assign role (multi role + kedaluwarsa) dan override menu.
 */
class AfselUserAccessController extends AfselAccessController
{
    /** Awalan kode user yang dibuat manual (bukan hasil sync WRS) agar tidak bentrok dengan kode WRS. */
    private const LOCAL_PREFIX = 'L';

    public function index(Request $request)
    {
        $type = $this->type($request);
        $cfg  = $this->rbac->config($type);
        $key  = $cfg['user_key'];
        $ur   = $this->table($type, 'user_roles');

        $stats = DB::table("{$cfg['user_table']} as u")->selectRaw('
                count(*) as total,
                coalesce(sum(u.is_active = 1), 0) as active,
                coalesce(sum(u.is_active = 0), 0) as inactive
            ')->first();
        $stats->no_role = DB::table("{$cfg['user_table']} as u")
            ->whereNotExists(fn ($q) => $q->from($ur)->whereColumn("{$ur}.{$key}", "u.{$key}"))
            ->count();

        return view('aftersales.atpm.page_access.users', $this->viewData($type, [
            'stats'     => $stats,
            // Pilihan dealer (filter & form): hanya dealer aktif
            'dealers'   => $type === 'dealer'
                ? DB::table('tbldealer')->where('is_active', true)->orderBy('nm_dealer')->get(['kd_dealer', 'nm_dealer'])
                : collect(),
            // Role awal saat tambah user: hanya role yang boleh dikelola user yang sedang login
            'roles'     => DB::table($this->table($type, 'roles'))->where('is_active', true)->orderBy('level')->orderBy('name')->get()
                ->filter(fn ($r) => $this->rbac->canManageRole($type, $r, $this->actor()))->values(),
            'canManageUser' => $this->can('rbac.user.manage'),
        ]));
    }

    public function datatable(Request $request)
    {
        $type = $this->type($request);
        $cfg  = $this->rbac->config($type);
        $key  = $cfg['user_key'];
        $ur   = $this->table($type, 'user_roles');
        $r    = $this->table($type, 'roles');

        $query = DB::table("{$cfg['user_table']} as u")
            ->select([
                "u.{$key} as kd_user", 'u.username', "u.{$cfg['user_name']} as name", 'u.email', 'u.is_active', 'u.last_login_at', 'u.created_by',
            ])
            ->selectSub(function ($q) use ($ur, $r, $key) {
                $q->from("{$ur} as ur")
                    ->join("{$r} as r", 'r.id', '=', 'ur.role_id')
                    ->whereColumn("ur.{$key}", "u.{$key}")
                    ->where(fn ($w) => $w->whereNull('ur.expires_at')->orWhere('ur.expires_at', '>', now()))
                    ->selectRaw("GROUP_CONCAT(r.name ORDER BY r.level SEPARATOR '||')");
            }, 'role_names');

        if ($type === 'dealer') {
            $query->addSelect(['u.fk_dealer', 'd.nm_dealer'])
                ->leftJoin('tbldealer as d', 'd.kd_dealer', '=', 'u.fk_dealer');

            if ($dealer = $request->input('fk_dealer')) {
                $query->where('u.fk_dealer', $dealer);
            }
        }

        switch ($request->input('status')) {
            case 'active':   $query->where('u.is_active', true); break;
            case 'inactive': $query->where('u.is_active', false); break;
        }

        if ($request->input('no_role') === '1') {
            $query->whereNotExists(fn ($q) => $q->from($ur)->whereColumn("{$ur}.{$key}", "u.{$key}"));
        }

        return DataTables::of($query)
            ->filterColumn('name', fn ($q, $kw) => $q->where("u.{$cfg['user_name']}", 'like', "%{$kw}%"))
            ->editColumn('last_login_at', fn ($row) => $row->last_login_at ? \Carbon\Carbon::parse($row->last_login_at)->format('d M Y H:i') : null)
            ->addColumn('is_local', fn ($row) => str_starts_with((string) $row->kd_user, self::LOCAL_PREFIX))
            ->addColumn('access_url', fn ($row) => route($this->context()['route'] . 'users.edit', ['kdUser' => $row->kd_user, 'type' => $type]))
            // Semua kolom dirender & di-escape di browser
            ->escapeColumns([])
            ->make(true);
    }

    /** Tambah user baru di luar sync WRS. */
    public function store(Request $request)
    {
        $type = $this->type($request);
        $cfg  = $this->rbac->config($type);
        $key  = $cfg['user_key'];

        $validator = $this->userValidator($request, $type, null);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }
        $data = $validator->validated();

        $role = null;
        if (!empty($data['role_id'])) {
            $role = DB::table($this->table($type, 'roles'))->where('id', $data['role_id'])->first();
            if (!$role || !$this->rbac->canManageRole($type, $role, $this->actor())) {
                return $this->fail('Anda tidak boleh memberikan role tersebut.', 403);
            }
        }

        $kdUser = DB::transaction(function () use ($type, $cfg, $key, $data, $role) {
            $kdUser = $this->nextLocalId($cfg['user_table'], $key);

            DB::table($cfg['user_table'])->insert(array_merge([
                $key                => $kdUser,
                $cfg['user_name']   => $data['name'],
                'username'          => $data['username'],
                'email'             => $data['email'] ?? null,
                'password'          => Hash::make($data['password']),
                'password_changed_at' => now(),
                'is_active'         => (bool) ($data['is_active'] ?? true),
                'created_by'        => $this->actor(),
                'date_created'      => now(),
            ], $type === 'dealer' ? ['fk_dealer' => $data['fk_dealer']] : []));

            if ($role) {
                DB::table($this->table($type, 'user_roles'))->insert([
                    $key => $kdUser, 'role_id' => $role->id, 'assigned_by' => $this->actor(), 'assigned_at' => now(),
                ]);
            }

            return $kdUser;
        });

        $this->rbac->flushCache($type);

        return $this->ok($role
            ? 'User berhasil ditambahkan.'
            : 'User berhasil ditambahkan. Atur role-nya lewat tombol "Akses" agar user bisa login.', ['kd_user' => $kdUser]);
    }

    /** Ubah data user (nama, username, email, dealer, status, password opsional). */
    public function updateProfile(Request $request, string $kdUser)
    {
        $type = $this->type($request);
        $cfg  = $this->rbac->config($type);
        $key  = $cfg['user_key'];

        $user = DB::table($cfg['user_table'])->where($key, $kdUser)->first();
        if (!$user) {
            return $this->fail('User tidak ditemukan.', 404);
        }

        $validator = $this->userValidator($request, $type, $kdUser);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }
        $data = $validator->validated();

        $isActive = (bool) ($data['is_active'] ?? $user->is_active);
        if ($type === 'atpm' && $kdUser === $this->actor() && !$isActive) {
            return $this->fail('Anda tidak bisa menonaktifkan akun Anda sendiri.', 403);
        }

        $update = [
            $cfg['user_name'] => $data['name'],
            'username'        => $data['username'],
            'email'           => $data['email'] ?? null,
            'is_active'       => $isActive,
            'updated_by'      => $this->actor(),
            'date_updated'    => now(),
        ];
        if ($type === 'dealer') {
            $update['fk_dealer'] = $data['fk_dealer'];
        }
        if (!empty($data['password'])) {
            $update['password']              = Hash::make($data['password']);
            $update['password_changed_at']   = now();
            $update['failed_login_attempts'] = 0;
            $update['locked_until']          = null;
        }

        DB::table($cfg['user_table'])->where($key, $kdUser)->update($update);
        $this->rbac->flushCache($type);

        return $this->ok('Data user berhasil diperbarui.');
    }

    private function userValidator(Request $request, string $type, ?string $ignoreKd)
    {
        $cfg   = $this->rbac->config($type);
        $table = $cfg['user_table'];

        $request->merge([
            'name'     => trim((string) $request->input('name')),
            'username' => trim((string) $request->input('username')),
            'email'    => trim((string) $request->input('email')) ?: null,
        ]);

        $rules = [
            'name'      => ['required', 'string', 'max:255'],
            'username'  => [
                'required', 'string', 'min:3', 'max:100', 'regex:/^[A-Za-z0-9._@\-]+$/',
                Rule::unique($table, 'username')->ignore($ignoreKd, $cfg['user_key']),
            ],
            'email'     => ['nullable', 'email', 'max:255'],
            'password'  => [$ignoreKd ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'is_active' => ['boolean'],
        ];

        if ($type === 'dealer') {
            $rules['fk_dealer'] = ['required', Rule::exists('tbldealer', 'kd_dealer')->where('is_active', true)];
        }
        if (!$ignoreKd) {
            $rules['role_id'] = ['nullable', 'integer', Rule::exists($this->table($type, 'roles'), 'id')];
        }

        return Validator::make($request->all(), $rules, [
            'name.required'      => 'Nama wajib diisi.',
            'username.required'  => 'Username wajib diisi.',
            'username.min'       => 'Username minimal 3 karakter.',
            'username.regex'     => 'Username hanya boleh huruf, angka, titik, strip, garis bawah, dan @.',
            'username.unique'    => 'Username sudah dipakai user lain.',
            'email.email'        => 'Format email tidak valid.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'fk_dealer.required' => 'Dealer wajib dipilih.',
            'fk_dealer.exists'   => 'Dealer tidak ditemukan atau sudah tidak aktif.',
        ]);
    }

    /** Kode user manual: L + YYYYMMDD + 20 digit nomor urut per hari. */
    private function nextLocalId(string $table, string $key): string
    {
        $prefix = self::LOCAL_PREFIX . now()->format('Ymd');

        $last = DB::table($table)->where($key, 'like', $prefix . '%')->lockForUpdate()->max($key);
        $seq  = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $seq, 20, '0', STR_PAD_LEFT);
    }

    /** Data user (dioverride Sales: akun ada di database WRS Sales). */
    protected function findUser(string $type, string $kdUser): ?object
    {
        $cfg = $this->rbac->config($type);

        return DB::table($cfg['user_table'])->where($cfg['user_key'], $kdUser)->first();
    }

    public function edit(Request $request, string $kdUser)
    {
        $type = $this->type($request);
        $cfg  = $this->rbac->config($type);
        $key  = $cfg['user_key'];

        $user = $this->findUser($type, $kdUser);
        abort_unless($user, 404);

        $assigned = DB::table($this->table($type, 'user_roles'))->where($key, $kdUser)->get()->keyBy('role_id');

        $roles = DB::table($this->table($type, 'roles'))->orderBy('level')->orderBy('name')->get()
            ->each(function ($role) use ($assigned, $type) {
                $pivot = $assigned[$role->id] ?? null;
                $role->assigned   = (bool) $pivot;
                $role->expires_at = $pivot?->expires_at;
                $role->expired    = $pivot?->expires_at && now()->gt($pivot->expires_at);
                $role->can_manage = $this->rbac->canManageRole($type, $role, $this->actor());
            });

        // Menu yang didapat dari role (gabungan semua role aktif user) — sebagai acuan override
        $activeRoles = $roles->filter(fn ($r) => $r->assigned && !$r->expired && $r->is_active);
        $roleMenuIds = $activeRoles->contains('is_system', true)
            ? DB::table($this->table($type, 'menus'))->pluck('id')->map(fn ($v) => (int) $v)->all()
            : DB::table($this->table($type, 'menu_roles'))->whereIn('role_id', $activeRoles->pluck('id'))->pluck('menu_id')->map(fn ($v) => (int) $v)->unique()->all();

        $overrides = DB::table($this->table($type, 'menu_users'))->where($key, $kdUser)->pluck('is_granted', 'menu_id')
            ->map(fn ($g) => $g ? 'grant' : 'revoke')->all();

        return view('aftersales.atpm.page_access.user_access', $this->viewData($type, [
            'user'        => $user,
            'kdUser'      => $kdUser,
            'userName'    => $user->{$cfg['user_name']},
            'roles'       => $roles,
            'menus'       => $this->menuTreeFlat($type),
            'roleMenuIds' => $roleMenuIds,
            'overrides'   => $overrides,
            'isSelf'      => $type === 'atpm' && $kdUser === $this->actor(),
        ]));
    }

    public function update(Request $request, string $kdUser)
    {
        $type = $this->type($request);
        $cfg  = $this->rbac->config($type);
        $key  = $cfg['user_key'];

        abort_unless($this->findUser($type, $kdUser), 404);

        $validator = Validator::make($request->all(), [
            'role_ids'     => ['array'],
            'role_ids.*'   => ['integer', Rule::exists($this->table($type, 'roles'), 'id')],
            'expires'      => ['array'],
            'expires.*'    => ['nullable', 'date'],
            'overrides'    => ['array'],
            'overrides.*'  => ['in:default,grant,revoke'],
        ], [
            'expires.*.date' => 'Format tanggal kedaluwarsa tidak valid.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $menuIds = DB::table($this->table($type, 'menus'))->pluck('id')->map(fn ($v) => (int) $v)->all();
        $overrides = collect($request->input('overrides', []))
            ->filter(fn ($mode, $menuId) => in_array((int) $menuId, $menuIds, true))
            ->all();

        // ---------- role ----------
        $allRoles  = DB::table($this->table($type, 'roles'))->get()->keyBy('id');
        $current   = DB::table($this->table($type, 'user_roles'))->where($key, $kdUser)->pluck('expires_at', 'role_id')->all();
        $submitted = array_map('intval', $request->input('role_ids', []));
        $expires   = $request->input('expires', []);

        $final = [];
        foreach ($allRoles as $roleId => $role) {
            $manageable = $this->rbac->canManageRole($type, $role, $this->actor());

            if ($manageable) {
                if (in_array($roleId, $submitted, true)) {
                    $final[$roleId] = !empty($expires[$roleId]) ? \Carbon\Carbon::parse($expires[$roleId])->format('Y-m-d H:i:s') : null;
                }
            } elseif (array_key_exists($roleId, $current)) {
                // Role di luar wewenang pengelola tidak boleh diubah → dipertahankan apa adanya
                $final[$roleId] = $current[$roleId];
            }
        }

        $norm = fn (array $roles) => collect($roles)
            ->mapWithKeys(fn ($exp, $id) => [(int) $id => $exp ? \Carbon\Carbon::parse($exp)->format('Y-m-d H:i') : ''])
            ->sortKeys()->all();
        $rolesChanged = $norm($final) !== $norm($current);
        $isSelf = $type === 'atpm' && $kdUser === $this->actor();

        if ($rolesChanged && $isSelf && !$this->isSuperAdmin()) {
            return $this->fail('Anda tidak bisa mengubah role akun Anda sendiri. Minta Super Admin untuk mengubahnya.', 403);
        }

        DB::transaction(function () use ($type, $kdUser, $final, $overrides, $rolesChanged) {
            if ($rolesChanged) {
                $this->rbac->saveUserRoles($type, $kdUser, $final, $this->actor());
            }
            $this->rbac->syncUserMenuOverrides($type, $kdUser, $overrides, $this->actor());
        });

        return $this->ok(empty($final)
            ? 'Akses disimpan. Perhatian: user ini tidak memiliki role sehingga tidak bisa login.'
            : 'Akses user berhasil disimpan.');
    }
}

@extends('aftersales.dealer.layouts.app')

@section('title', 'Profile')

@section('navtop')
    {{ view('aftersales.dealer.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.dealer.layouts.sidebar') }}
@endsection

@section('content')
@php
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('m/d/Y H:i') : null;
    $words = preg_split('/\s+/', trim((string) $user->nm_dealer_user), -1, PREG_SPLIT_NO_EMPTY);
    $initials = collect($words)->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'U';
@endphp
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Profile</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.dealer.home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
        </nav>
    </div>

    <div class="row g-3">
        {{-- Data user & dealer (read only) --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <div class="pf-head">
                        <div class="pf-avatar" aria-hidden="true">{{ $initials }}</div>
                        <div class="min-w-0">
                            <div class="pf-name">{{ $user->nm_dealer_user }}</div>
                            <div class="afsel-muted small">{{ $user->username }}</div>
                        </div>
                    </div>

                    <dl class="pf-list">
                        <dt>Group Dealer</dt>
                        <dd>
                            @if ($dealer?->fk_dealer_group)
                                {{ $groupName ?: $dealer->fk_dealer_group }}
                                @if ($groupName)<span class="afsel-muted">({{ $dealer->fk_dealer_group }})</span>@endif
                            @else
                                <span class="afsel-muted">-</span>
                            @endif
                        </dd>

                        <dt>Nama Dealer</dt>
                        <dd>
                            @if ($dealer)
                                {{ $dealer->nm_dealer }} <span class="afsel-muted">({{ $dealer->kd_dealer }})</span>
                            @else
                                <span class="afsel-muted">{{ $user->fk_dealer ?: '-' }}</span>
                            @endif
                        </dd>

                        @if ($dealer?->fk_dealer_region)
                            <dt>Region</dt>
                            <dd>{{ $dealer->fk_dealer_region }}</dd>
                        @endif

                        <dt>Nama</dt>
                        <dd>{{ $user->nm_dealer_user }}</dd>

                        <dt>Role</dt>
                        <dd>
                            @if ($roleName)
                                <span class="afsel-tag is-red"><i class="bi bi-person-badge me-1"></i>{{ $roleName }}</span>
                            @else
                                <span class="afsel-muted">-</span>
                            @endif
                            @if ($canSwitchRole)
                                <a href="{{ route('aftersales.role.select') }}" class="btn-fi btn-fi-secondary btn-fi-sm ms-2">
                                    <i class="bi bi-arrow-left-right"></i> Ganti Role
                                </a>
                            @endif
                        </dd>

                        <dt>Email</dt>
                        <dd id="pf-email">{!! $user->email ? e($user->email) : '<span class="afsel-muted">-</span>' !!}</dd>

                        <dt>Login terakhir</dt>
                        <dd>{!! $fmt($user->last_login_at) ?? '<span class="afsel-muted">-</span>' !!}</dd>

                        <dt>Password diubah</dt>
                        <dd>{!! $fmt($user->password_changed_at) ?? '<span class="afsel-muted">belum pernah</span>' !!}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7 d-flex flex-column gap-3">
            {{-- Ganti email --}}
            <div class="card">
                <div class="card-header"><span class="afsel-section-title"><i class="bi bi-envelope"></i> Ganti Email</span></div>
                <div class="card-body">
                    <form id="form-email" autocomplete="off" onsubmit="return false" novalidate>
                        <div class="mb-2">
                            <label class="form-label" for="email">Email baru</label>
                            <input type="email" class="form-control form-control-sm" id="email" name="email" maxlength="150" required
                                   value="{{ $user->email }}" placeholder="nama@dealer.co.id">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="email-current-password">Password saat ini</label>
                            <input type="password" class="form-control form-control-sm" id="email-current-password" name="current_password"
                                   required autocomplete="current-password">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn-fi btn-fi-primary btn-fi-sm"><i class="bi bi-save"></i> Simpan Email</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Reset password --}}
            <div class="card">
                <div class="card-header"><span class="afsel-section-title"><i class="bi bi-key"></i> Reset Password</span></div>
                <div class="card-body">
                    <form id="form-password" autocomplete="off" onsubmit="return false" novalidate>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="new-password">Password baru</label>
                                <input type="password" class="form-control form-control-sm" id="new-password" name="password"
                                       required minlength="8" autocomplete="new-password">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="new-password-confirm">Ulangi password baru</label>
                                <input type="password" class="form-control form-control-sm" id="new-password-confirm" name="password_confirmation"
                                       required minlength="8" autocomplete="new-password">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="show-password">
                            <label class="form-check-label small" for="show-password">Tampilkan password</label>
                        </div>
                        <div class="form-text mb-3">Minimal 8 karakter, berisi huruf dan angka, berbeda dari password saat ini.</div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn-fi btn-fi-primary btn-fi-sm"><i class="bi bi-shield-lock"></i> Ubah Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('aftersales.components.profile-assets', [
    'emailUrl'    => route('aftersales.dealer.profile.email'),
    'passwordUrl' => route('aftersales.dealer.profile.password'),
])
@endsection

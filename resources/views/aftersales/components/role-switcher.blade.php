{{-- Info role aktif + link ganti role (RBAC After Sales). Dipakai di dropdown navtop. --}}
@if (config('afsel.auth_driver') === 'local' && ($afselActiveRole = afsel_role()))
    <li>
        <span class="dropdown-item-text small">
            <i class="bi bi-person-badge me-1"></i>Role: <strong>{{ $afselActiveRole->name }}</strong>
        </span>
    </li>
    @if (app(\App\Services\Afsel\AfselRbacService::class)->getAssignedRoles(afsel_type(), (string) session('user.id'))->count() > 1)
        <li>
            <a class="dropdown-item" href="{{ route('aftersales.role.select') }}">
                <i class="bi bi-arrow-left-right me-1"></i>Ganti Role
            </a>
        </li>
    @endif
@endif

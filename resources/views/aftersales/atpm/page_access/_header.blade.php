{{--
    Header halaman Access Control.
    Param: $title, $tabRoute (route untuk tab ATPM/Dealer, null = tanpa tab), $crumbs (array title/url), $tabParams (opsional)
--}}
<div class="page-header flex-wrap gap-2">
    <div class="page-title">{{ $title }}</div>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route($acx['home'] ?? 'aftersales.atpm.home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="javascript:void(0)">Access Control</a></li>
            @foreach ($crumbs ?? [] as $crumb)
                <li class="breadcrumb-item"><a href="{{ $crumb['url'] ?? 'javascript:void(0)' }}">{{ $crumb['title'] }}</a></li>
            @endforeach
        </ol>
    </nav>
</div>

@if (!empty($tabRoute))
    <ul class="nav access-type-tabs mb-3">
        @foreach (['atpm' => 'ATPM', 'dealer' => 'Dealer'] as $tabType => $tabLabel)
            <li class="nav-item">
                <a class="nav-link {{ $type === $tabType ? 'active' : '' }}"
                   href="{{ route($tabRoute, ($tabParams ?? []) + ['type' => $tabType]) }}"
                   @if ($type === $tabType) aria-current="page" @endif>
                    {{ $tabLabel }}
                </a>
            </li>
        @endforeach
    </ul>
@endif

{{-- Gaya tampilan ada di public/assets/css/afsel-ui.css (kelas .afsel-ui) --}}
<style>
    .menu-indent { display: inline-block; }
</style>

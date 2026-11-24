{{-- Favicon Mazda 2026 — dipakai di Sales & After Sales (ATPM + Dealer). Naikkan $v jika file ikon diganti agar cache browser ter-refresh. --}}
@php $v = '2026'; @endphp
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $v }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ $v }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ $v }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ $v }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}?v={{ $v }}">

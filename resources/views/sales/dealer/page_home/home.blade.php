@extends('sales.dealer.layouts.app')

@section('title', 'Home - Sales Dealer')

@section('navtop')
    {{ view('sales.dealer.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('sales.dealer.layouts.sidebar') }}
@endsection

@section('content')
    <div class="content">
        {{-- $SalesDealerMenuUser diisi view composer (menu RBAC Sales Dealer sesuai role aktif) --}}
        @include('sales.components.home', ['portal' => 'Dealer', 'menus' => $SalesDealerMenuUser ?? collect()])
    </div>
@endsection

@extends('sales.atpm.layouts.app')

@section('title', 'Home - Sales ATPM')

@section('navtop')
    {{ view('sales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('sales.atpm.layouts.sidebar') }}
@endsection

@section('content')
    <div class="content">
        {{-- $SalesAtpmMenuUser diisi view composer (menu RBAC Sales ATPM sesuai role aktif) --}}
        @include('sales.components.home', ['portal' => 'ATPM', 'menus' => $SalesAtpmMenuUser ?? collect()])
    </div>
@endsection

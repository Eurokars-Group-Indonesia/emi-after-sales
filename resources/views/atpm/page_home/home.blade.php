@extends('atpm.layouts.app')

@section('title', 'Home')

@section('navtop')
    {{ view('atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('atpm.layouts.sidebar') }}
@endsection


@php

    $breadcrumbs = [
        ['title' => 'Home', 'url' => '#']
    ];
@endphp



@section('content')
    <div class="content">
        <div class="page-header">
            <div class="page-title">Home</div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    @foreach ($breadcrumbs as $item)
                        <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card p-3">
                    <h6 class="text-muted">Total Users</h6>
                    <h3>1,200</h3>
                </div>
            </div>
        </div>
    </div>
@endsection
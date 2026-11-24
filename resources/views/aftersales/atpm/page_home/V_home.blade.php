@extends('aftersales.atpm.layouts.app')

@section('title', 'Home')

@section('navtop')
    {{ view('aftersales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.atpm.layouts.sidebar') }}
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

        
    </div>
@endsection
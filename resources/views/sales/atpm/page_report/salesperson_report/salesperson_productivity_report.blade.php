@extends('sales.atpm.layouts.app')

@section('title', 'Sales Person Productivity Report')

@section('navtop')
    {{ view('sales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('sales.atpm.layouts.sidebar') }}
@endsection


@php
    $breadcrumbs = [
        ['title' => 'Home', 'url' => '#']
    ];
@endphp



@section('content')

    
    <div class="content">
        <!-- <div class="page-header">
            <div class="page-title">Sales Person Productivity Report</div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    @foreach ($breadcrumbs as $item)
                        <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
        </div> -->


        <style>
            th.text-medium{
                color:#519ee3!important;
            }
        </style>
        <div class="card">
            <div class="card-body">
                <iframe
                    src="http://192.168.1.25:3000/public/question/966b1b86-a687-4f4b-a157-fdeea71f2861"
                    style="
                        width:100%;
                        height:100vh;
                        border:none;
                        display:block;
                        overflow:hidden;
                    ">
                </iframe>
            </div>
        </div>
        
    </div>
@endsection
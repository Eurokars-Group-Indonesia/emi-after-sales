@extends('atpm.layouts.app')

@section('title', 'Sync Information')

@section('navtop')
    {{ view('atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('atpm.layouts.sidebar') }}
@endsection

@php

    $breadcrumbs = [
        ['title' => 'Home', 'url' => route('atpm.aftersales.home')],
        ['title' => 'Sync Information', 'url' => 'javascript:void(0)'],
    ];
@endphp


@section('content')

    @csrf

    <div class="content">
        <div class="page-header">
            <div class="page-title">Sync Information</div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    @foreach ($breadcrumbs as $item)
                        <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
        </div>

        <div class="row g-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body py-5">

                        {{-- @if (session('information')) --}}
                        <div class="text-center py-4">

                            <div class="sync-icon-wrapper mx-auto mb-4">
                                <i class="bi bi-arrow-repeat sync-spin"></i>
                            </div>

                            <h5 class="fw-semibold text-dark mb-2">Sinkronisasi Sedang Berjalan</h5>
                            <p class="text-muted mb-4" style="font-size:15px;">WRS After Sales to New Database</p>

                            <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-pill" style="background:#fff8e1; border:1px solid #ffe082;">
                                <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                                <span class="text-warning fw-medium" style="font-size:13px;">Harap tunggu, proses ini membutuhkan beberapa saat...</span>
                            </div>

                        </div>
                        {{-- @endif --}}

                    </div>
                </div>
            </div>
        </div>

        <style>
            .sync-icon-wrapper {
                width: 90px;
                height: 90px;
                border-radius: 50%;
                background: linear-gradient(135deg, #e3f0ff, #cce0ff);
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 20px rgba(0, 62, 136, 0.15);
            }

            .sync-icon-wrapper i {
                font-size: 42px;
                color: #003f88;
            }

            .sync-spin {
                display: inline-block;
                animation: spin 1.8s linear infinite;
            }

            @keyframes spin {
                from { transform: rotate(0deg); }
                to   { transform: rotate(360deg); }
            }
        </style>
    @endsection

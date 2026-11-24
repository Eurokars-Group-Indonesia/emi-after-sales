@extends('aftersales.atpm.layouts.app')

@section('title', 'Tambah Model Other')

@section('navtop')
    {{ view('aftersales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.atpm.layouts.sidebar') }}
@endsection

@section('content')
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Tambah Model Other</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.atpm.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('aftersales.atpm.model_other') }}">Model Other</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-8">
            <div class="card">
                <div class="card-header">
                    <span class="afsel-section-title">Pilih Model</span>
                    <span class="afsel-sub">Model yang ditambahkan ikut dihitung saat filter model <strong>Other</strong> dipilih di Report Retention.</span>
                </div>
                <div class="card-body">
                    <form id="form-model-other" onsubmit="return false">
                        @if ($dataModel->isEmpty())
                            <div class="afsel-note mb-0">
                                <i class="bi bi-check2-circle"></i>
                                <span>Semua model After Sales sudah masuk daftar Model Other.</span>
                            </div>
                        @else
                            <label class="form-label" for="kd-model">Model</label>
                            <select class="form-select form-select-sm" id="kd-model" name="kd_model" data-afsel-search>
                                <option value="">- Pilih model -</option>
                                @foreach ($dataModel as $model)
                                    <option value="{{ $model->kd_model }}" data-label="{{ $model->nm_model }}" data-code="{{ $model->kd_model }}">{{ $model->nm_model }} ({{ $model->kd_model }})</option>
                                @endforeach
                            </select>
                            <div class="form-text">Cari berdasarkan nama atau kode model. Hanya model yang belum ada di daftar yang ditampilkan ({{ $dataModel->count() }} model).</div>
                        @endif

                        <div class="d-flex gap-2 justify-content-end mt-3">
                            <a href="{{ route('aftersales.atpm.model_other') }}" class="btn-fi btn-fi-secondary btn-fi-sm">Kembali</a>
                            @if ($dataModel->isNotEmpty())
                                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-save"><i class="bi bi-save"></i> Simpan</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('aftersales.atpm.page_access._scripts')
@include('aftersales.atpm.components.searchable-select')
<script>
    $(function () {
        const form = document.getElementById('form-model-other');
        const modelSelect = form.kd_model ? afselSearchSelect(form.kd_model) : null;

        $('#btn-save').on('click', function () {
            if (!modelSelect.validate('Pilih model terlebih dahulu.')) {
                return;
            }
            afselRequest({
                method: 'post',
                url: '{{ route('aftersales.atpm.model_other_store') }}',
                data: afselFormData(form),
                confirm: `Tambahkan ${form.kd_model.selectedOptions[0].text} ke daftar Model Other?`,
                reload: false,
            }).then(res => { if (res) window.location.href = '{{ route('aftersales.atpm.model_other') }}'; });
        });
    });
</script>
@endsection

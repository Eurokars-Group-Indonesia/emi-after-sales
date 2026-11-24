@extends('aftersales.dealer.layouts.app')

@section('title', 'Upload Service')

@section('navtop')
    {{ view('aftersales.dealer.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.dealer.layouts.sidebar') }}
@endsection

@section('content')
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Upload Service</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.dealer.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Service</a></li>
                <li class="breadcrumb-item active" aria-current="page">Upload Service</li>
            </ol>
        </nav>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center flex-wrap gap-2">
                    <span class="afsel-section-title">Upload File Excel</span>
                    <div class="ms-auto d-flex flex-wrap gap-2">
                        <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm su-btn-rules" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-rules" aria-controls="offcanvas-rules">
                            <i class="bi bi-info-circle-fill"></i> Ketentuan Upload
                        </button>
                        <a href="{{ route('aftersales.dealer.service.service-data') }}" class="btn-fi btn-fi-secondary btn-fi-sm">
                            <i class="bi bi-table"></i> Data Service
                        </a>
                        <a href="{{ route('aftersales.dealer.service.service-upload-template') }}" class="btn-fi btn-fi-secondary btn-fi-sm su-btn-excel">
                            <i class="bi bi-file-earmark-excel-fill"></i> Download Template
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form id="form-upload" autocomplete="off" onsubmit="return false">
                        <label for="upload-file" class="su-drop" id="drop-zone">
                            <i class="bi bi-cloud-arrow-up su-drop-icon" aria-hidden="true"></i>
                            <span class="su-drop-title" id="drop-title">Klik atau tarik file Excel ke sini</span>
                            <span class="su-drop-hint" id="drop-hint">Hanya .xlsx &middot; maks. {{ $maxFileMb }} MB &middot; maks. {{ number_format($maxRows, 0, ',', '.') }} baris</span>
                            <input type="file" id="upload-file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="visually-hidden">
                        </label>

                        <div class="d-flex gap-2 justify-content-end mt-3">
                            <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" id="btn-clear" disabled>
                                <i class="bi bi-x-lg"></i> Batal
                            </button>
                            <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-upload" disabled>
                                <i class="bi bi-upload"></i> Upload &amp; Proses
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    {{-- Ketentuan Upload (offcanvas kanan) --}}
    <div class="offcanvas offcanvas-end afsel-ui su-offcanvas" tabindex="-1" id="offcanvas-rules" aria-labelledby="offcanvas-rules-title">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title afsel-section-title" id="offcanvas-rules-title">
                <i class="bi bi-info-circle-fill"></i> Ketentuan Upload
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body su-rules">
            <ul class="mb-0">
                <li>Gunakan <strong>template terbaru</strong>; data diisi mulai baris ke-2 sheet <em>Data</em>.</li>
                <li><strong>No Polisi</strong> &amp; <strong>No Vin</strong>: huruf besar &amp; angka, tanpa spasi/simbol.</li>
                <li><strong>Tanggal Service</strong> format <code>mm/dd/yyyy</code>, tidak boleh melebihi tanggal hari ini.</li>
                <li><strong>Kode Service</strong>: {{ implode(', ', $serviceCodes) }}.</li>
                <li><strong>Nama SA / Nama Teknisi</strong> harus sama persis (huruf besar/kecil) dengan personel aktif dealer Anda.</li>
                <li>VIN harus sudah <strong>delivery</strong> (BAST <em>TerBast</em>).</li>
                <li><strong>Nama STNK</strong> disimpan sebagai nama customer, <strong>Nama Pemakai</strong> sebagai nama pemakai.</li>
                <li>VIN yang sudah terdaftar: <strong>data customer diperbarui</strong> sesuai Excel jika Tanggal Service adalah yang terbaru. Kolom opsional yang kosong tidak menghapus data yang ada.</li>
                <li class="su-rule-strong">Jika ada 1 baris saja yang error, <strong>seluruh file tidak disimpan</strong>. Perbaiki / keluarkan baris tersebut dari Excel lalu upload ulang.</li>
            </ul>
        </div>
        <div class="su-offcanvas-footer">
            <a href="{{ route('aftersales.dealer.service.service-data') }}" class="btn-fi btn-fi-primary btn-fi-sm w-100 justify-content-center">
                <i class="bi bi-table"></i> Lihat Data Service
            </a>
        </div>
    </div>

    {{-- Hasil upload --}}
    <div class="card mt-3 d-none" id="result-card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <span class="afsel-section-title" id="result-title">Hasil Upload</span>
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm d-none" id="btn-export-errors">
                    <i class="bi bi-download"></i> Download Daftar Error (.csv)
                </button>
                <a href="{{ route('aftersales.dealer.service.service-data') }}" class="btn-fi btn-fi-primary btn-fi-sm d-none" id="btn-goto-data">
                    <i class="bi bi-table"></i> Lihat Data Service
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="su-alert" id="result-message" role="status"></div>

            <div class="afsel-stats d-none" id="result-summary">
                <div class="afsel-stat"><span class="afsel-stat-num" id="sum-kpi">0</span><span class="afsel-stat-label">Data service (tblkpi)</span></div>
                <div class="afsel-stat"><span class="afsel-stat-num" id="sum-new">0</span><span class="afsel-stat-label">Customer baru</span></div>
                <div class="afsel-stat"><span class="afsel-stat-num" id="sum-upd">0</span><span class="afsel-stat-label">Customer diupdate</span></div>
            </div>

            <div class="d-none" id="result-errors">
                <table class="table table-sm table-hover su-errors" id="table-errors">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:80px">Baris</th>
                            <th style="width:140px">No Polisi</th>
                            <th style="width:200px">No Vin</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .afsel-ui .su-btn-excel i { color: #1d6f42; }
    .afsel-ui .su-drop {
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
        min-height: 190px; padding: 24px; border: 2px dashed var(--mz-line); border-radius: 10px;
        background: var(--mz-bg-soft); cursor: pointer; text-align: center; transition: border-color .15s, background .15s;
    }
    .afsel-ui .su-drop:hover, .afsel-ui .su-drop.is-drag { border-color: var(--mz-red); background: #fff; }
    .afsel-ui .su-drop.has-file { border-style: solid; border-color: var(--mz-ink); background: #fff; }
    .afsel-ui .su-drop:focus-within { outline: 2px solid var(--mz-red); outline-offset: 2px; }
    .afsel-ui .su-drop-icon { font-size: 34px; color: var(--mz-red); }
    .afsel-ui .su-drop.has-file .su-drop-icon { color: #1d6f42; }
    .afsel-ui .su-drop-title { font-weight: 600; color: var(--mz-ink); word-break: break-all; }
    .afsel-ui .su-drop-hint { font-size: 12px; color: var(--mz-muted); }
    .afsel-ui .su-rules { font-size: 13px; }
    .afsel-ui .su-rules li { margin-bottom: 6px; }
    .afsel-ui .su-rule-strong { color: var(--mz-red); }
    .afsel-ui .su-btn-rules i { color: var(--mz-red); }
    .su-offcanvas { width: 420px; max-width: 100vw; }
    .su-offcanvas .offcanvas-header { border-bottom: 1px solid var(--mz-line); }
    .su-offcanvas .offcanvas-title i { color: var(--mz-red); margin-right: 4px; }
    .su-offcanvas .su-rules ul { padding-left: 18px; }
    .su-offcanvas-footer { padding: 12px 16px; border-top: 1px solid var(--mz-line); }
    .afsel-ui .su-alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; border: 1px solid; }
    .afsel-ui .su-alert.is-ok { background: #ecfdf3; border-color: #a6e9c3; color: #146c43; }
    .afsel-ui .su-alert.is-err { background: #fff1f2; border-color: #f5c2c7; color: #9b1c2e; }
    .afsel-ui .su-errors td { font-size: 12.5px; vertical-align: top; }
    .afsel-ui .su-errors ul { margin: 0; padding-left: 18px; }
    .afsel-ui .su-errors .su-mono { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 12px; }
</style>

<script>
    $(function () {
        const input = document.getElementById('upload-file');
        const drop = document.getElementById('drop-zone');
        const btnUpload = document.getElementById('btn-upload');
        const btnClear = document.getElementById('btn-clear');
        const defaultTitle = $('#drop-title').text();
        let errorsTable = null;
        let lastErrors = [];

        const esc = v => $('<div>').text(v ?? '').html();

        const setFile = file => {
            const ok = !!file;
            drop.classList.toggle('has-file', ok);
            $('.su-drop-icon').toggleClass('bi-cloud-arrow-up', !ok).toggleClass('bi-file-earmark-excel-fill', ok);
            $('#drop-title').text(ok ? file.name : defaultTitle);
            btnUpload.disabled = !ok;
            btnClear.disabled = !ok;
        };

        // Hanya .xlsx yang diterima (.xls ditolak)
        const isXlsx = file => {
            if (/\.xlsx$/i.test(file.name)) return true;
            Swal.fire({
                icon: 'warning',
                title: 'File tidak valid',
                text: 'File harus berformat .xlsx. File .xls tidak didukung, simpan ulang (Save As) sebagai Excel Workbook (.xlsx).',
                confirmButtonColor: '#1b2027',
            });
            return false;
        };

        input.addEventListener('change', () => {
            const file = input.files[0];
            if (file && !isXlsx(file)) {
                input.value = '';
                setFile(null);
                return;
            }
            setFile(file);
        });

        ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('is-drag'); }));
        ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('is-drag'); }));
        drop.addEventListener('drop', e => {
            const file = e.dataTransfer.files[0];
            if (!file) return;
            if (!isXlsx(file)) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            setFile(file);
        });

        btnClear.addEventListener('click', () => { input.value = ''; setFile(null); });

        const renderResult = data => {
            $('#result-card').removeClass('d-none');
            const ok = data.status === true;

            $('#result-title').text(ok ? 'Upload Berhasil' : 'Upload Gagal');
            $('#result-message').removeClass('is-ok is-err').addClass(ok ? 'is-ok' : 'is-err')
                .html(`<i class="bi ${ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}"></i> ${esc(data.message)}`);

            $('#result-summary').toggleClass('d-none', !ok);
            $('#btn-goto-data').toggleClass('d-none', !ok);
            if (ok) {
                $('#sum-kpi').text((data.summary.kpi_inserted || 0).toLocaleString('id-ID'));
                $('#sum-new').text((data.summary.customer_inserted || 0).toLocaleString('id-ID'));
                $('#sum-upd').text((data.summary.customer_updated || 0).toLocaleString('id-ID'));
            }

            lastErrors = data.errors || [];
            const hasErrors = lastErrors.length > 0;
            $('#result-errors').toggleClass('d-none', !hasErrors);
            $('#btn-export-errors').toggleClass('d-none', !hasErrors);

            if (errorsTable) {
                errorsTable.destroy();
                errorsTable = null;
            }
            const rows = lastErrors.map(e => `
                <tr>
                    <td class="text-center">${e.row}</td>
                    <td class="su-mono">${esc(e.no_polisi) || '-'}</td>
                    <td class="su-mono">${esc(e.no_vin) || '-'}</td>
                    <td><ul>${e.messages.map(m => `<li>${esc(m)}</li>`).join('')}</ul></td>
                </tr>`).join('');
            $('#table-errors tbody').html(rows);

            if (hasErrors) {
                errorsTable = $('#table-errors').DataTable({
                    order: [[0, 'asc']],
                    pageLength: 25,
                    language: {
                        search: 'Cari:',
                        lengthMenu: 'Tampilkan _MENU_ baris',
                        info: '_START_ - _END_ dari _TOTAL_ baris error',
                        infoFiltered: '(difilter dari _MAX_)',
                        zeroRecords: 'Tidak ditemukan',
                        paginate: { previous: '‹', next: '›' },
                    },
                });
            }

            document.getElementById('result-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        btnUpload.addEventListener('click', () => {
            const file = input.files[0];
            if (!file) return;

            Swal.fire({
                title: 'Upload data service?',
                html: `File <strong>${esc(file.name)}</strong> akan divalidasi lalu disimpan.<br>Jika ada baris yang error, tidak ada data yang disimpan.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, upload',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#1b2027',
            }).then(res => {
                if (!res.isConfirmed) return;

                const form = new FormData();
                form.append('file', file);

                Swal.fire({
                    title: 'Memproses upload...',
                    html: 'Validasi &amp; simpan data service. Mohon tunggu, jangan tutup halaman ini.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                axios.post('{{ route('aftersales.dealer.service.service-upload-store') }}', form, {
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    timeout: 600000,
                })
                .then(r => {
                    Swal.close();
                    renderResult(r.data);
                    input.value = '';
                    setFile(null);
                })
                .catch(err => {
                    Swal.close();
                    const data = err.response?.data;
                    if (data && typeof data === 'object' && 'status' in data) {
                        renderResult(data);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload gagal',
                            text: err.response?.status === 403
                                ? 'Anda tidak punya akses Upload Service.'
                                : (err.response?.status === 413 ? 'Ukuran file terlalu besar.' : 'Terjadi kesalahan. Silakan coba lagi.'),
                            confirmButtonColor: '#1b2027',
                        });
                    }
                });
            });
        });

        // Daftar error → CSV (untuk dicocokkan dengan file Excel)
        $('#btn-export-errors').on('click', () => {
            const q = v => `"${String(v ?? '').replace(/"/g, '""')}"`;
            const lines = [['Baris', 'No Polisi', 'No Vin', 'Error'].map(q).join(',')]
                .concat(lastErrors.map(e => [e.row, e.no_polisi, e.no_vin, e.messages.join(' | ')].map(q).join(',')));
            const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = Object.assign(document.createElement('a'), { href: url, download: 'Upload-Service-Errors.csv' });
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        });
    });
</script>
@endsection

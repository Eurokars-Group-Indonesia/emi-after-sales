@extends('atpm.layouts.app')

@section('title', 'Retention Report')

@section('navtop')
    {{ view('atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('atpm.layouts.sidebar') }}
@endsection

@php
    $breadcrumbs = [
        ['title' => 'Home', 'url' => '#'],
        ['title' => 'Report', 'url' => '#'],
        ['title' => 'Retention Report', 'url' => 'javascript:void(0)'],
    ];
@endphp

@section('content')

    <span id="data-detail-uio" style="display:none;"></span>
    <span id="data-detail-customer-visit" style="display:none;"></span>

    <div class="content">
        <div class="page-header">
            <div class="page-title">Report Retention</div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    @foreach ($breadcrumbs as $item)
                        <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
        </div>


        <div class="row">
            <div class="col-12">
                <div class="card">
                    {{-- <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-tag"></i> Report Retention</span>
                    </div> --}}
                    <div class="card-body">
                        <form id="parameterSearch">
                            @csrf
                            <div class="row mb-3">

                                <div class="col-md-6">
                                    <div class="input-group mb-3">
                                        <select name="kd_dealer[]" id="kd_dealer" class="form-control" multiple>
                                            @foreach ($dataDealer as $dealer)
                                                <option value="{{ $dealer->kd_dealer }}">{{ $dealer->nm_dealer }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="input-group mb-3">
                                        <select name="tahun" id="tahun" class="form-control">
                                            <option value=""selected>- Tahun -</option>
                                            <option value="2026">2026</option>
                                            <option value="2025">2025</option>
                                            <option value="2024">2024</option>
                                            <option value="2023">2023</option>
                                        </select>
                                    </div>
                                    <div class="input-group mb-3">
                                        <select name="category_customer" id="category_customer" class="form-control">
                                            <option value=""selected>- Category Customer -</option>
                                            <option value="without">Customer Paid(Without 1.000km Check)</option>
                                            <option value="with">Customer Paid(With 1.000km Check)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group mb-3">
                                        <select name="kd_model[]" id="kd_model" class="form-control" multiple>
                                            @foreach ($dataModel as $model)
                                                <option value="{{ $model->kd_model }}">{{ $model->nm_model }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="input-group mb-3">
                                        <select name="uio" id="uio" class="form-control">
                                            <option value=""selected>- UIO -</option>
                                            @foreach ($dataUio as $row_dataUio)
                                                <option value="{{ $row_dataUio->kd_uio }}">{{ $row_dataUio->deskripsi }}
                                                </option>
                                            @endforeach




                                            {{-- <option value="1styears">1st Years</option>
                                        <option value="2ndyears">2nd Years</option>
                                        <option value="3rdyears">3rd Years</option>
                                        <option value="4thyears">4th Years</option>
                                        <option value="5thyears">5th Years</option>
                                        <option value="6thyears">6th Years</option>
                                        <option value="7thyears">7th Years</option>
                                        <option value="overall3Years">Overall 3 Years</option>
                                        <option value="overall7Years">Overall 7 Years</option> --}}
                                        </select>
                                    </div>
                                    <div class="input-group mb-3">
                                        <select name="including_vin" id="including_vin" class="form-control">
                                            <option value=""selected>- Including VIN sold by other dealer -</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- <div class="col-md-12">
                                <div class="row" style="padding:5px; background:#efefef;border-radius:5px;">
                                    <div class="col-md-3 ">
                                        <btn class="btn btn-primary" id="search-report" style="float:left; margin:3px;">
                                            Search
                                        </btn>

                                        <div class="spinner-border" id="spinner-load" style="position:absolute; left:100px; margin:3px; display:none;">
                                            <span class="sr-only"></span>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div> --}}

                                <style>
                                    .report-container-1 {
                                        background-color: #efefef;
                                        /* bisa diganti sesuai tema */
                                        border-radius: 5px;
                                    }

                                    /* Optional: untuk spinner positioning lebih rapi */
                                    #spinner-load {
                                        margin-left: 10px;
                                        /* jarak dari tombol */
                                    }
                                </style>

                                <div class="col-12">
                                    <div class="report-container-1 p-2 rounded bg-light" style="padding:0px!important;">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 d-flex align-items-center">
                                                <button class="btn btn-primary me-2" id="search-report">
                                                    Search
                                                </button>
                                                <div class="spinner-border" id="spinner-load" style="display: none;">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>




                            </div>
                        </form>
                    </div>



                    <style>
                        #report table thead tr th {
                            text-align: center;
                        }
                    </style>

                    <table class="table table-bordered table-hover">
                        <tbody id="report-body">
                            <!-- Row Target (%) -->
                            <tr>
                                <td style="background:#efefef; width:200px">Retention Report</td>
                                <td colspan="12"></td>
                            </tr>
                        </tbody>
                    </table>


                </div>
            </div>
        </div>
        {{-- </div> --}}


{{-- 

        <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script> --}}

        <script type="text/javascript">
            $('body').on('click', '#search-report', function() {

                $('#search-report').prop('disabled', true);
                $('#report-body').empty();

                const formElement = document.querySelector("#parameterSearch");
                const formData = new FormData(formElement);

                $('#spinner-load').css('display', 'block');

                axios.post('{{ route('atpm.report.report-retention-retrieve') }}', formData, {
                        headers: {
                            // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        }
                    })
                    .then(function(response) {

                        console.log('ini',response);

                        $('#search-report').prop('disabled', false);
                        $('#spinner-load').css('display', 'none');

                        
                        var status = response.data.status;
                        

                        if (status) {
                            // console.log(true);
                            // console.log(response);
                            const data = response.data.reportRetention;
                            const tbody = document.getElementById('report-body');

                            $('#spinner-load').css('display', 'none');

                            // Buat row untuk setiap field
                            const fields = ["rangePeriode", "bulan", "customer_visit", "uio", "service_retention",
                                "gap"
                            ];
                            const labels = ["rangePeriode", "Bulan", "Customer Visit", "UIO", "Service Retention",
                                "Gap"
                            ];

                            fields.forEach((field, idx) => {
                                const tr = document.createElement('tr');

                                // Kolom pertama = label
                                const tdLabel = document.createElement('td');
                                tdLabel.textContent = labels[idx];
                                tdLabel.style.background = '#efefef';
                                tdLabel.style.background = '#efefef';
                                tdLabel.style.whiteSpace = 'nowrap';
                                tr.appendChild(tdLabel);

                                // // Kolom data = isi dari JSON
                                // data.forEach(item => {
                                //     const td = document.createElement('td');
                                //     td.textContent = item[field];
                                //     td.style.textAlign = 'center';
                                //     tr.appendChild(td);
                                // });
                                data.forEach(item => {
                                    const td = document.createElement('td');

                                    if (field === 'rangePeriode') {
                                        td.innerHTML = (item[field] || '').replace(/\r?\n/g,
                                        "<br>");
                                    } else if(field == 'service_retention') {
                                        td.textContent = item[field]+' %';
                                    }
                                    else {
                                        td.textContent = item[field];
                                    }

                                    td.style.textAlign = 'center';
                                    tr.appendChild(td);
                                });

                                tbody.appendChild(tr);
                            });
                        } else {

                            // $('#spinner-load').css('display', 'none');

                            console.log(true);
                            var errors = response.data.errors;
                            var messageError = '';

                            Object.keys(errors).forEach(field => {
                                messageError += errors[field][0] + '\n';
                            });

                            alert(messageError);

                            return;
                        }
                    })
                    .catch(function(error) {
                        console.log(error);
                        alert('catch');



                        $('#search-report').prop('disabled', false);

                        // $('#search-report').prop('disabled', false);
                    });
            });
        </script>

    </div>
@endsection

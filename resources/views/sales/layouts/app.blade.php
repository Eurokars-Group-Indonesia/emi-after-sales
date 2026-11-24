{{--
    Layout WRS Connect Sales (ATPM & Dealer).
    Struktur sama dengan layout After Sales (sidebar bersama aftersales.components.sidebar),
    tetapi memakai tema biru Sales: sidebar tema 'sales' + public/assets/css/sales-ui.css (body.sales-theme).
    Dipakai lewat sales.atpm.layouts.app / sales.dealer.layouts.app.
--}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    @include('partials.favicon')
    
    <!-- jquery -->
    <script src="{{ asset('assets/js/jquery4.js') }}"></script>

    <!-- axios -->
    <script src="{{ asset('assets/vendor/axios/axios.min.js') }}"></script>

    <!-- bootstrap -->    
    <link href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/vendor/bootstrap/bootstrap-icons.css') }}" rel="stylesheet" />

    <!-- datatable -->
    <link href="{{ asset('assets/vendor/datatable/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <script src="{{ asset('assets/vendor/datatable/dataTables.min.js') }}"></script>

    

    <!-- sweet alert -->
    <script src="{{ asset('assets/vendor/sweetalert2/sweetaler2@11.js') }}"></script>

    
    <!-- mzd style -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/datatable-style.css') }}" rel="stylesheet" />
    <!-- komponen halaman admin (Access Control) + tema biru Sales -->
    <link href="{{ asset('assets/css/afsel-ui.css') }}?v={{ @filemtime(public_path('assets/css/afsel-ui.css')) }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/sales-ui.css') }}?v={{ @filemtime(public_path('assets/css/sales-ui.css')) }}" rel="stylesheet" />

    

    <!-- css general -->
    <style>
        body {
            font-size:0.8rem;
        }
        #disabler {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;

            background: rgba(0,0,0,0.8); /* lebih bagus dari opacity */
            z-index: 1000;
            color: #ffffff;

            display: flex;
            justify-content: center;
            align-items: center;
        }


        body {
            background: #f4f6f9;
            overflow-x: hidden;
        }

        /* Overlay for mobile */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            opacity: 0;
            visibility: hidden;
            transition: .3s;
            z-index: 998;
        }

        .overlay.show {
            opacity: 1;
            visibility: visible;
        }

        /* Sidebar: style ada di aftersales.components.sidebar (tema 'sales') */

        /* Desktop layout */
        .content {
            margin-left: 250px;
            padding: 25px;
            transition: margin-left .3s ease;
        }

        .navbar-custom {
            margin-left: 250px;
            transition: margin-left .3s ease;
            background: #ffffff !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid #e5e7eb;
        }

        /* Mobile behavior */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .content {
                margin-left: 0;
            }

            .navbar-custom {
                margin-left: 0;
            }
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: 0.3s;
        }

        /* .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        } */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 22px;
            font-weight: 600;
            color: #0b2a52;
        }

        ol.breadcrumb .active {
            color:black!important;
        }

        ol.breadcrumb li a{
            text-decoration:none;
            color:black!important;
            font-size:14px;
        }
    </style>

    <!-- css datatable -->
    <link href="{{ asset('assets/vendor/datatable/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/vendor/datatable/buttons.bootstrap5.min.css') }}" rel="stylesheet" />

    <style>
        /* compact */
        table.dataTable thead th,
        table.dataTable tbody td {
            padding: 5px 8px !important;
            font-size: 0.82rem;
            line-height: 1.4;
        }
        table.dataTable thead th { white-space: nowrap; }

        /* stripe: odd = putih, even = biru muda */
        table.dataTable tbody tr:nth-child(odd)  > td { background-color: #ffffff !important; }
        table.dataTable tbody tr:nth-child(even) > td { background-color: #ddeeff !important; }

        /* hover */
        table.dataTable tbody tr:hover > td { background-color: #b8d9f8 !important; }
    </style>
</head>

<body class="sales-theme">

    <div id="disabler" style="display:none;">
        <div class="spinner-border" id="spinner-load"></div> &nbsp; Please Wait...
    </div>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    @yield('sidebar')

    @yield('navtop')

    @yield('content')

    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>

    <!-- Datatable -->
    <script src="{{ asset('assets/vendor/datatable/dataTables.min_1_13_8.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatable/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatable/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatable/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatable/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatable/buttons.html5.min.js') }}"></script>



    <!-- <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script> -->
    <!-- <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script> -->
    <!-- <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script> -->
    <!-- <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script> -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script> -->
    <!-- <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script> -->

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                sidebar.style.marginLeft = sidebar.style.marginLeft === '-250px' ? '0' : '-250px';
                document.querySelector('.content').style.marginLeft = sidebar.style.marginLeft === '0px' ? '250px' : '0';
                document.querySelector('.navbar-custom').style.marginLeft = sidebar.style.marginLeft === '0px' ? '250px' :
                    '0';
            }
        }

        function closeSidebar() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }

        // Toggle sub menu & search menu: lihat aftersales.components.sidebar
    </script>

</body>

</html>

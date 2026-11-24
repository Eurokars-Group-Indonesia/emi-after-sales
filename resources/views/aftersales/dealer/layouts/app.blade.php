<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'After Sales Dealer')</title>
    @include('partials.favicon')
    
    <!-- jquery -->
    <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>

    <!-- axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!-- bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- datatable -->
    <link href="https://cdn.datatables.net/2.3.7/css/dataTables.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/2.3.7/js/dataTables.min.js"></script>

    <!-- sweet alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- mzd style: tombol btn-fi & tampilan After Sales (sama dengan ATPM, hanya aktif di dalam .afsel-ui) -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/afsel-ui.css') }}?v={{ @filemtime(public_path('assets/css/afsel-ui.css')) }}" rel="stylesheet" />

    <!-- css general -->
    <style>
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

        /* Sidebar: style ada di aftersales.components.sidebar */

        /* Desktop layout */
        .content {
            margin-left: 250px;
            padding: 25px;
        }

        .navbar-custom {
            margin-left: 250px;
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
            color: #002856;
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
</head>

<body>

    <div id="disabler" style="display:none;">
        <div class="spinner-border" id="spinner-load"></div> &nbsp; Please Wait...
    </div>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    @yield('sidebar')

    @yield('navtop')

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    
    <!-- jquery -->
    <script src="{{ asset('assets/js/jquery4.js') }}"></script>

    <!-- axios -->
    <script src="{{ asset('assets/vendor/axios/axios.min.js') }}"></script>

    <!-- bootstrap -->    
    <link href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/vendor/bootstrap/bootstrap-icons.css') }}" rel="stylesheet" />

    <!-- datatable -->
    <link href="{{ asset('assets/vendor/datatable/dataTables.dataTables.min.css') }}" rel="stylesheet" />
    <script src="{{ asset('assets/vendor/datatable/dataTables.min.js') }}"></script>


    <!-- sweet alert -->
    <script src="{{ asset('assets/vendor/sweetalert2/sweetaler2@11.js') }}"></script>

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

        /* Sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(180deg, #002856, #003f88);
            color: #fff;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.15);
            z-index: 999;
            transition: transform .3s ease;
        }

        .sidebar a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            color: #cbd5e1;
            text-decoration: none;
            transition: 0.2s;
            position: relative;
        }

        .sidebar a i {
            margin-right: 10px;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            padding-left: 25px;
        }

        .active {
            color: #fff !important;
            font-weight: 500;
        }

        .active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #0d6efd;
            border-radius: 0 4px 4px 0;
        }

        .active i {
            color: #0d6efd;
        }

        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding-left: 15px;
        }

        .submenu.open {
            max-height: 500px;
        }

        .submenu a {
            font-size: 14px;
        }

        .arrow {
            transition: transform 0.3s;
        }

        .arrow.rotate {
            transform: rotate(180deg);
        }

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

    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>

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

        document.querySelectorAll('.menu-toggle').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.getElementById(this.dataset.target);
                const parent = this.parentElement;

                parent.querySelectorAll('.submenu.open').forEach(el => {
                    if (el !== target) {
                        el.classList.remove('open');
                        const arrow = el.previousElementSibling?.querySelector('.arrow');
                        arrow?.classList.remove('rotate');
                    }
                });

                target.classList.toggle('open');
                this.querySelector('.arrow').classList.toggle('rotate');
            });
        });

        document.querySelectorAll('.menu-link').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                closeSidebar();
            });
        });
    </script>

</body>

</html>

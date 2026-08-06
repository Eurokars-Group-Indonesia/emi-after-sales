<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WRS Portal</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="style.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {

            font-family: 'Poppins', sans-serif;

            background: #f6f6f6;

            overflow: hidden;

            position: relative;

            height: 100vh;

        }

        /* Background */

        /* .background-overlay {

            position: absolute;
            inset: 0;

            background:
                linear-gradient(rgba(255, 255, 255, .90),
                    rgba(255, 255, 255, .92)),
                url("https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1800&q=80");

            background-size: cover;
            background-position: center;
            background-attachment: fixed;

            filter: grayscale(100%);
        } */

        .background-overlay {
            position: absolute;
            inset: 0;
            background:#efefef;
            filter: grayscale(100%);
        }

        /* Main */

        .container {

            position: relative;

            z-index: 2;

            height: 100vh;

            display: flex;

            flex-direction: column;

            justify-content: center;

            align-items: center;

        }

        header {

            text-align: center;

            margin-bottom: 60px;

        }

        .logo {

            width: 120px;

            margin-bottom: 25px;

        }

        header h1 {

            font-size: 58px;

            font-weight: 700;

            color: #111;

            letter-spacing: 8px;

        }

        .subtitle {

            margin-top: 8px;

            color: #666;

            font-size: 18px;

            letter-spacing: 2px;

        }

        /* CARD */

        .menu {

            display: flex;

            gap: 35px;

            flex-wrap: wrap;

            justify-content: center;

        }

        .card {

            width: 340px;

            /*height: 250px;*/

            background: #fff;

            border-radius: 22px;

            text-decoration: none;

            color: #222;

            padding: 35px;

            transition: .35s;

            box-shadow:
                0 15px 35px rgba(0, 0, 0, .08);

            display: flex;

            flex-direction: column;

            justify-content: space-between;

            border: 1px solid rgba(0, 0, 0, .05);

        }

        .card:hover {

            transform: translateY(-12px);

            box-shadow:
                0 30px 55px rgba(0, 0, 0, .15);

            border-color: #c00000;

        }

        .icon {

            font-size: 45px;

        }

        .card h2 {

            font-size: 28px;

            font-weight: 600;

        }

        .card p {

            color: #666;

            line-height: 1.7;

            font-size: 15px;

        }

        .card span {

            color: #c00000;

            font-weight: 600;

        }

        /* Footer */

        footer {

            position: absolute;

            bottom: 25px;

            width: 100%;

            text-align: center;

            z-index: 3;

            color: #777;

            letter-spacing: 1px;

            font-size: 13px;

        }

        /* Mobile */

        @media(max-width:900px) {

            body {
                overflow: auto;
            }

            .container {

                height: auto;

                padding: 60px 20px;

            }

            .menu {

                flex-direction: column;

                align-items: center;

            }

            .card {

                width: 100%;

                max-width: 380px;

            }

        }
    </style>
</head>

<body>

    <div class="background-overlay"></div>

    <div class="container">

        <header>

            <img src="{{ asset('assets/img/mazda-logo-26.png') }}" class="logo">

            <h1>WRS</h1>

            <!-- <p class="subtitle">
                Warehouse Reporting System
            </p> -->

        </header>

        <section class="menu">

            <a href="/sales" class="card">

                <!-- <div class="icon">
                    📊
                </div> -->

                <h2>WRS Sales</h2>

                <!-- <p>
                    Sales Dashboard, Report, Analytics and Monitoring
                </p> -->

                <span>Open Application →</span>

            </a>

            <a href="/after_sales" class="card">

                <!-- <div class="icon">
                    🔧
                </div> -->

                <h2>WRS After Sales</h2>

                <!-- <p>
                    Service, Parts, Warranty and Dealer Performance
                </p> -->

                <span>Open Application →</span>

            </a>

        </section>

    </div>

    <footer>
        © 2026 WRS
    </footer>

</body>

</html>
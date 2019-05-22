<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title')</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

    <!-- Styles -->
    <style>
        html,
        body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 200;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .links>a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }

        .footer {
            margin-top: 30px;
        }

        .nav {
            padding-top: 50px;
        }
    </style>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
</head>

<body>
    <nav class="nav links flex-center">
        <a href="/upload-file/Air">Upload Air file</a>
        <a href="/upload-file/Soap">Upload SOAP file</a>
    </nav>

    <div class="flex-center position-ref full-height">
        <div class="content">
            @yield('content')

            <div class="footer">
                <p>made with <i class="fa fa-heart"></i> by Alfredo Altamirano</p>
                <div class="links">
                    <a href="https://www.github.com/ahuahuachi" target="_blank"><i class="fab fa-github fa-2x"></i></a>
                    <a href="https://www.facebook.com/ahuahuachi" target="_blank"><i class="fab fa-facebook fa-2x"></i></a>
                    <a href="https://www.twitter.com/FabulosoFredy" target="_blank"><i class="fab fa-twitter fa-2x"></i></a>
                    <a href="https://www.linkedin.com/in/alfredo-altamirano-tena" target="_blank"><i class="fab fa-linkedin fa-2x"></i></a>
                </div>
                <p>powered by <i class="fab fa-laravel"></i> Laravel</p>
            </div>
        </div>
    </div>

</body>

</html>

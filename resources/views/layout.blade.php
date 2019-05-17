<!DOCTYPE html>
<html lang="en">

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
            height: 90vh;
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
    </style>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
</head>

<body>
    {{-- <nav>Navigation placeholder</div> --}}

    @yield('content')

    <footer class="content">
        <div>
            powered by <i class="fab fa-laravel"></i> Laravel
        </div>
        <div>
            made with <i class="fa fa-heart"></i> by Alfredo Altamirano
        </div>
        <div>
            <a href="https://www.github.com/ahuahuachi" target="_blank"><i class="fab fa-github"></i></a>
            <a href="https://www.facebook.com/ahuahuachi" target="_blank"><i class="fab fa-facebook"></i></a>
            <a href="https://www.twitter.com/FabulosoFredy" target="_blank"><i class="fab fa-twitter"></i></a>
            <a href="https://www.linkedin.com/in/alfredo-altamirano-tena" target="_blank"><i class="fab fa-linkedin"></i></a>
        </div>
    </footer>
</body>

</html>

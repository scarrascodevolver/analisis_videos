<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Rugby Key Performance - Sistema de Análisis de Video')</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

    <!-- Rugby Custom Styles -->
    <style>
        :root {
            --color-primary: #005461;
            --color-primary-hover: #003d4a;
            --color-secondary: #4A6274;
            --color-accent: #D4A017;
            --color-bg: #F4F4F4;
            --color-bg-card: #FFFFFF;
            --color-text: #333333;
        }

        .rugby-green {
            background-color: var(--color-primary) !important;
        }

        .text-rugby {
            color: var(--color-primary) !important;
        }

        .btn-rugby {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            color: white;
        }

        .btn-rugby:hover {
            background-color: var(--color-primary-hover);
            border-color: var(--color-primary-hover);
            color: white;
        }

        .login-page, .register-page {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-hover) 50%, var(--color-secondary) 100%);
            min-height: 100vh;
        }

        .card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
        }

        .card-outline.card-primary {
            border-top: 3px solid var(--color-primary);
        }

        .register-box {
            width: 450px;
            margin: 3% auto;
        }

        .login-box {
            width: 360px;
            margin: 7% auto;
        }

        .rugby-ball-bg {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            font-size: 20rem;
            z-index: -1;
            color: white;
        }

        .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 84, 97, 0.25);
        }

        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }

        .btn-primary:hover {
            background-color: var(--color-primary-hover);
            border-color: var(--color-primary-hover);
        }

        .rugby-logo {
            transition: transform 0.3s ease;
        }

        .rugby-logo:hover {
            transform: scale(1.05);
        }

        .fas.fa-rugby-ball:before {
            content: "\f44e";
        }

        /* Fallback for rugby ball icon */
        .fas.fa-rugby-ball:before {
            content: "\f1e3"; /* football icon as fallback */
        }
    </style>

    @yield('css')
</head>
<body class="hold-transition @yield('body-class', 'login-page')">
    <!-- Rugby Ball Background -->
    <div class="rugby-ball-bg">
        <i class="fas fa-rugby-ball"></i>
    </div>

    @yield('auth-content')

    <!-- jQuery -->
    <script src="{{ asset('vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>

    @yield('js')
</body>
</html>
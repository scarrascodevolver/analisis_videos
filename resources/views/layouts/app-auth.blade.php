<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Los Troncos - Sistema de Análisis Rugby')</title>

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
        .rugby-green {
            background-color: #1e4d2b !important;
        }
        
        .text-rugby {
            color: #1e4d2b !important;
        }
        
        .btn-rugby {
            background-color: #1e4d2b;
            border-color: #1e4d2b;
            color: white;
        }
        
        .btn-rugby:hover {
            background-color: #2d5a3a;
            border-color: #2d5a3a;
            color: white;
        }
        
        .login-page, .register-page {
            background: linear-gradient(135deg, #1e4d2b 0%, #2d5a3a 50%, #4a7c59 100%);
            min-height: 100vh;
        }
        
        .card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        .card-outline.card-primary {
            border-top: 3px solid #1e4d2b;
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
            border-color: #1e4d2b;
            box-shadow: 0 0 0 0.2rem rgba(30, 77, 43, 0.25);
        }
        
        .btn-primary {
            background-color: #1e4d2b;
            border-color: #1e4d2b;
        }
        
        .btn-primary:hover {
            background-color: #2d5a3a;
            border-color: #2d5a3a;
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
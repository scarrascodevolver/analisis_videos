<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Los Troncos - Sistema de Análisis Rugby')</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_lt.png') }}">

    <!-- Bootstrap 4 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(rgba(30, 77, 43, 0.7), rgba(45, 90, 58, 0.8)), url('{{ asset('tineo.jpg') }}') center/cover no-repeat fixed;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        
        .auth-header {
            background: #1e4d2b;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .auth-header h3 {
            margin: 0;
            font-weight: bold;
        }
        
        .auth-header p {
            margin: 5px 0 0 0;
            opacity: 0.8;
        }
        
        .auth-body {
            padding: 40px;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 16px;
        }
        
        .form-control:focus {
            border-color: #1e4d2b;
            box-shadow: 0 0 0 0.2rem rgba(30, 77, 43, 0.25);
        }
        
        .btn-rugby {
            background: #1e4d2b;
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-weight: bold;
            color: white;
            transition: all 0.3s;
        }
        
        .btn-rugby:hover {
            background: #2d5a3a;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-outline-rugby {
            border: 2px solid #1e4d2b;
            color: #1e4d2b;
            background: transparent;
            border-radius: 8px;
            padding: 10px 25px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-outline-rugby:hover {
            background: #1e4d2b;
            color: white;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-left: none;
            border-radius: 0 8px 8px 0;
        }
        
        .input-group .form-control {
            border-right: none;
            border-radius: 8px 0 0 8px;
        }
        
        .input-group select.form-control {
            height: auto !important;
            line-height: 1.5 !important;
            padding: 12px 15px !important;
        }
        
        .step-indicator {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .progress-bar {
            background: #1e4d2b;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .text-muted {
            color: #6c757d !important;
        }
        
        a {
            color: #1e4d2b;
            text-decoration: none;
        }
        
        a:hover {
            color: #2d5a3a;
            text-decoration: underline;
        }
        
        .registration-step {
            min-height: 400px;
        }
        
        .form-check-label {
            font-size: 14px;
        }
        
        .logo-icon {
            font-size: 3rem;
            margin-bottom: 10px;
            color: white;
        }

        @media (max-width: 576px) {
            .auth-body {
                padding: 30px 20px;
            }
            
            .auth-header {
                padding: 20px;
            }
        }
    </style>
    
    @yield('css')
</head>
<body>
    @yield('content')

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @yield('js')
</body>
</html>
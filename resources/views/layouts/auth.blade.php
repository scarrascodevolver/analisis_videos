<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Rugby Key Performance - Sistema de Análisis de Video para Rugby')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Bootstrap 4 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        /* ========================================
           VARIABLES CSS CENTRALIZADAS
           ======================================== */
        :root {
            --color-primary: #005461;
            --color-primary-hover: #003d4a;
            --color-secondary: #4A6274;
            --color-accent: #4B9DA9;
            --color-bg: #F4F4F4;
            --color-bg-card: #FFFFFF;
            --color-text: #333333;
        }

        body {
            background-color: var(--color-primary);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        /* Background with Image */
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(0, 84, 97, 0.3) 0%, rgba(74, 98, 116, 0.3) 100%),
                        url('/rugby-ball.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .video-background video {
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            object-fit: cover;
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }

        .auth-header {
            background: rgba(0, 84, 97, 0.3);
            color: white;
            padding: 15px;
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
            color: white;
        }

        .auth-body h5 {
            color: white;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px 15px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-control:focus {
            border-color: rgba(255, 255, 255, 0.6);
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }

        .btn-rugby {
            background: var(--color-primary);
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-weight: bold;
            color: white;
            transition: all 0.3s;
        }

        .btn-rugby:hover {
            background: var(--color-primary-hover);
            color: white;
            transform: translateY(-2px);
        }

        .btn-outline-rugby {
            border: 2px solid var(--color-primary);
            color: var(--color-primary);
            background: transparent;
            border-radius: 8px;
            padding: 10px 25px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-outline-rugby:hover {
            background: var(--color-primary);
            color: white;
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-left: none;
            border-radius: 0 8px 8px 0;
            color: rgba(255, 255, 255, 0.8);
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
            background: var(--color-primary);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .text-muted {
            color: #6c757d !important;
        }

        a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
        }

        a:hover {
            color: white;
            text-decoration: underline;
        }

        .registration-step {
            min-height: 400px;
        }

        .form-check-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
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
    <!-- Background with rugby ball image -->
    <div class="video-background"></div>

    @yield('content')

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @yield('js')
</body>
</html>
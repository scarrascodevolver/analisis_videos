<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $currentOrganization = auth()->check() ? auth()->user()->currentOrganization() : null;
        $orgFavicon = $currentOrganization && $currentOrganization->logo_path
            ? asset('storage/' . $currentOrganization->logo_path)
            : asset('favicon.png');
    @endphp
    <link rel="icon" type="image/png" href="{{ $orgFavicon }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- AdminLTE CSS -->
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <!-- Bebas Neue Font -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">

    <style>
        /* Prevent horizontal overflow */
        html, body { overflow-x: hidden; max-width: 100%; }
        .wrapper, .content-wrapper { overflow-x: hidden; }

        /* CSS Variables */
        :root {
            --color-primary: #005461;
            --color-primary-hover: #003d4a;
            --color-secondary: #018790;
            --color-accent: #D4A017;
            --color-bg: #0f0f0f;
            --color-bg-card: #0f0f0f;
            --color-text: #ffffff;
        }

        /* Utility classes */
        .btn-rugby { background-color: var(--color-primary); border-color: var(--color-primary); color: white; }
        .btn-rugby:hover { background-color: var(--color-primary-hover); border-color: var(--color-primary-hover); color: white; }
        .btn-rugby-light { background-color: var(--color-secondary); border-color: var(--color-secondary); color: white; }
        .btn-rugby-light:hover { background-color: var(--color-primary); border-color: var(--color-primary); color: white; }
        .btn-rugby-dark { background-color: var(--color-primary-hover); border-color: var(--color-primary-hover); color: white; }
        .btn-rugby-dark:hover { background-color: #002830; border-color: #002830; color: white; }
        .btn-rugby-outline { background-color: transparent; border: 1px solid var(--color-accent); color: var(--color-accent); }
        .btn-rugby-outline:hover { background-color: var(--color-accent); color: white; }
        .btn-outline-rugby { background-color: transparent; border-color: var(--color-primary); color: var(--color-primary); }
        .btn-outline-rugby:hover { background-color: var(--color-primary); border-color: var(--color-primary); color: white; }
        .badge-rugby { background-color: var(--color-accent); color: white; }
        .rugby-green { background-color: var(--color-primary) !important; color: white; }
        .text-rugby { color: var(--color-primary) !important; }

        /* Navbar */
        .main-header.navbar { background-color: var(--color-primary) !important; }

        /* Sidebar */
        .main-sidebar { background-color: var(--color-primary-hover) !important; display: flex; flex-direction: column; height: 100vh; }
        .nav-sidebar .nav-link { color: #c2c7d0; font-size: 0.78rem; padding: 0.4rem 0.7rem; }
        .nav-sidebar .nav-link:hover { background-color: var(--color-secondary); color: white; }
        .nav-sidebar .nav-link.active { background-color: var(--color-accent) !important; color: white !important; }
        .nav-sidebar .nav-icon { font-size: 0.85rem; margin-right: 0.5rem; }
        .nav-header { font-size: 0.6rem; padding: 0.35rem 0.7rem; font-weight: 600; letter-spacing: 0.5px; }
        .brand-link { background-color: var(--color-primary) !important; color: white !important; border-bottom: 1px solid var(--color-secondary); padding: 0.6rem 0.7rem; }
        .brand-link .brand-image { max-height: 28px; }
        .brand-logo-full { display: block; }
        .brand-logo-mini { display: none !important; }
        .sidebar-collapse .brand-logo-full { display: none !important; }
        .sidebar-collapse .brand-logo-mini { display: block !important; }
        .user-panel { padding: 0.6rem 0.7rem; }
        .user-panel .info { color: #c2c7d0; font-size: 0.78rem; }
        .user-panel .image { width: 2rem; height: 2rem; }
        .sidebar { flex: 1 1 auto; overflow-y: auto; padding-bottom: 16px; }

        /* Content area */
        .content-wrapper { background-color: var(--color-bg); }
        .content-header h1 { color: var(--color-text); }

        /* Cards */
        .card { background-color: var(--color-bg-card); color: var(--color-text); border: 1px solid var(--color-secondary); }
        .card .text-muted { color: #aaaaaa !important; }
        .card-header, .card-title, .card-footer { color: var(--color-text); }

        /* Forms */
        .form-control { background-color: var(--color-primary-hover); border-color: var(--color-secondary); color: var(--color-text); }
        .form-control:focus { background-color: var(--color-primary); border-color: var(--color-accent); color: var(--color-text); }
        .form-control::placeholder { color: #aaaaaa; }
        select.form-control option { background-color: var(--color-primary-hover); color: var(--color-text); }

        /* Tables */
        .table { color: var(--color-text); }
        .table thead th { background-color: var(--color-primary-hover); color: var(--color-text); border-color: var(--color-secondary); }
        .table td, .table th { border-color: var(--color-secondary); }
        .table-hover tbody tr:hover { background-color: var(--color-primary-hover) !important; color: var(--color-text) !important; }

        /* Footer */
        .main-footer { background-color: var(--color-primary-hover); color: white; border-top: 1px solid var(--color-secondary); }
        .main-footer a { color: var(--color-accent); }

        /* Lists */
        .list-group-item { background-color: var(--color-bg-card); color: var(--color-text); border-color: var(--color-secondary); }

        /* Breadcrumbs */
        .breadcrumb-item.active { color: #aaaaaa; }
        .breadcrumb-item a { color: var(--color-primary) !important; }
        .breadcrumb-item a:hover { color: var(--color-secondary) !important; text-decoration: none; }

        /* Links */
        a { color: var(--color-primary); }
        a:hover { color: var(--color-secondary); text-decoration: none; }

        /* Bootstrap overrides */
        .bg-success { background-color: var(--color-accent) !important; }
        .text-success { color: var(--color-accent) !important; }
        .btn-success { background-color: var(--color-accent); border-color: var(--color-accent); }
        .btn-success:hover { background-color: var(--color-secondary); border-color: var(--color-secondary); }
        .text-gray-800 { color: var(--color-text) !important; }

        /* Dropdowns */
        .dropdown-item:active, .dropdown-item:focus { background-color: var(--color-primary) !important; color: white !important; }
        .dropdown-item:hover { background-color: rgba(0, 84, 97, 0.15); }
        .dropdown-menu { background-color: #1a1a1a; border-color: var(--color-secondary); }
        .dropdown-menu .dropdown-item { color: var(--color-text); }
        .dropdown-divider { border-top-color: var(--color-secondary); }

        /* Video player page specific */
        .video-card { transition: all 0.3s; background-color: var(--color-bg-card); }
        .video-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }

        /* Mobile fixes */
        @media (max-width: 576px) {
            .user-dropdown-menu { position: absolute; right: 0; left: auto; min-width: 150px; }
            .navbar .dropdown-menu { max-width: calc(100vw - 20px); }
        }
        @media (max-width: 350px) {
            .dropdown-menu-lg { min-width: auto !important; max-width: calc(100vw - 20px) !important; }
        }
    </style>

    @vite(['resources/js/app.ts'])
    @inertiaHead
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    @inertia

    <!-- jQuery (requerido por AdminLTE) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>

</html>

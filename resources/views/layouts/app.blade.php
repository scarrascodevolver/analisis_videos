<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $currentOrganization = auth()->check() ? auth()->user()->currentOrganization() : null;
        $orgName = $currentOrganization ? $currentOrganization->name : 'RugbyHub';
        $orgLogo =
            $currentOrganization && $currentOrganization->logo_path
                ? asset('storage/' . $currentOrganization->logo_path)
                : asset('logohub.png');
        $orgFavicon =
            $currentOrganization && $currentOrganization->logo_path
                ? asset('storage/' . $currentOrganization->logo_path)
                : asset('favicon.png');
    @endphp
    <title>@yield('page_title', 'Dashboard') - {{ $orgName }}</title>
    <link rel="icon" type="image/png" href="{{ $orgFavicon }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- AdminLTE CSS -->
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css"
        rel="stylesheet" />
    <!-- Bebas Neue Font -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <!-- Fabric.js para anotaciones -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>

    <style>
        /* ========================================
           VARIABLES CSS CENTRALIZADAS
           ======================================== */
        :root {
            --color-primary: #005461;
            --color-primary-hover: #003d4a;
            --color-secondary: #018790;
            --color-accent: #00B7B5;
            --color-bg: #0f0f0f;
            --color-bg-card: #0f0f0f;
            --color-text: #ffffff;
        }

        /* ========================================
           CLASES UTILITARIAS
           ======================================== */
        .rugby-green {
            background-color: var(--color-primary) !important;
            color: white;
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

        .btn-outline-rugby {
            background-color: transparent;
            border-color: var(--color-primary);
            color: var(--color-primary);
        }

        .btn-outline-rugby:hover {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            color: white;
        }

        .badge-rugby {
            background-color: var(--color-accent);
            color: white;
        }

        .badge-rugby-light {
            background-color: var(--color-secondary);
            color: white;
        }

        /* ========================================
           LAYOUT - NAVBAR Y SIDEBAR
           ======================================== */
        .main-header.navbar {
            background-color: var(--color-primary) !important;
        }

        .main-sidebar {
            background-color: var(--color-primary-hover) !important;
        }

        .nav-sidebar .nav-link {
            color: #c2c7d0;
        }

        .nav-sidebar .nav-link:hover {
            background-color: var(--color-secondary);
            color: white;
        }

        .nav-sidebar .nav-link.active {
            background-color: var(--color-accent) !important;
            color: white !important;
        }

        .brand-link {
            background-color: var(--color-primary) !important;
            color: white !important;
            border-bottom: 1px solid var(--color-secondary);
        }

        /* Logo switching for collapsed sidebar */
        .brand-logo-full { display: block; }
        .brand-logo-mini { display: none !important; }
        .sidebar-collapse .brand-logo-full { display: none !important; }
        .sidebar-collapse .brand-logo-mini { display: block !important; }

        .user-panel .info {
            color: #c2c7d0;
        }

        /* ========================================
           COMPONENTES
           ======================================== */
        .card {
            background-color: var(--color-bg-card);
            color: var(--color-text);
            border: 1px solid var(--color-secondary);
        }

        .card .text-muted {
            color: #aaaaaa !important;
        }

        .card-header,
        .card-title,
        .card-footer {
            color: var(--color-text);
        }

        .breadcrumb-item.active {
            color: #aaaaaa;
        }

        .list-group-item {
            background-color: var(--color-bg-card);
            color: var(--color-text);
            border-color: var(--color-secondary);
        }

        .text-gray-800 {
            color: var(--color-text) !important;
        }

        .table {
            color: var(--color-text);
        }

        .table thead th {
            background-color: var(--color-primary-hover);
            color: var(--color-text);
            border-color: var(--color-secondary);
        }

        .table td,
        .table th {
            border-color: var(--color-secondary);
        }

        .table-hover tbody tr:hover {
            background-color: var(--color-primary-hover) !important;
            color: var(--color-text) !important;
        }

        .table-hover tbody tr:hover td {
            color: var(--color-text) !important;
        }

        .thead-light th {
            background-color: var(--color-primary-hover) !important;
            color: var(--color-text) !important;
        }

        .form-control {
            background-color: var(--color-primary-hover);
            border-color: var(--color-secondary);
            color: var(--color-text);
        }

        .form-control:focus {
            background-color: var(--color-primary);
            border-color: var(--color-accent);
            color: var(--color-text);
        }

        .form-control::placeholder {
            color: #aaaaaa;
        }

        select.form-control option {
            background-color: var(--color-primary-hover);
            color: var(--color-text);
        }

        .info-box-rugby {
            background: linear-gradient(45deg, var(--color-primary), var(--color-secondary));
            color: white;
        }

        .info-box-rugby .info-box-text,
        .info-box-rugby .info-box-number {
            color: white;
        }

        .card-rugby {
            border-top: 3px solid var(--color-primary);
        }

        .small-box .icon {
            font-size: 70px;
            top: 10px;
            right: 15px;
            position: absolute;
            opacity: 0.3;
        }

        .content-wrapper {
            background-color: var(--color-bg);
        }

        .content-header h1 {
            color: var(--color-text);
        }

        .main-footer {
            background-color: var(--color-primary-hover);
            color: white;
            border-top: 1px solid var(--color-secondary);
        }

        .main-footer a {
            color: var(--color-accent);
        }

        .video-card {
            transition: all 0.3s;
            background-color: var(--color-bg-card);
        }

        .video-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* ========================================
           UPCOMING FEATURES
           ======================================== */
        .upcoming-feature {
            opacity: 0.8;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upcoming-feature:hover {
            opacity: 1;
            background-color: rgba(0, 84, 97, 0.1) !important;
        }

        .upcoming-feature .badge {
            font-size: 0.65rem;
            padding: 2px 6px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        /* ========================================
           ENLACES Y BREADCRUMBS
           ======================================== */
        .breadcrumb-item a {
            color: var(--color-primary) !important;
        }

        .breadcrumb-item a:hover {
            color: var(--color-secondary) !important;
            text-decoration: none;
        }

        a {
            color: var(--color-primary);
        }

        a:hover {
            color: var(--color-secondary);
            text-decoration: none;
        }

        /* ========================================
           OVERRIDES BOOTSTRAP
           ======================================== */
        .bg-success {
            background-color: var(--color-accent) !important;
        }

        .text-success {
            color: var(--color-accent) !important;
        }

        .btn-success {
            background-color: var(--color-accent);
            border-color: var(--color-accent);
        }

        .btn-success:hover {
            background-color: var(--color-secondary);
            border-color: var(--color-secondary);
        }

        /* Dropdown items - sobrescribir azul de Bootstrap */
        .dropdown-item:active,
        .dropdown-item:focus {
            background-color: var(--color-primary) !important;
            color: white !important;
        }

        .dropdown-item:hover {
            background-color: rgba(0, 84, 97, 0.15);
            /* #005461 con transparencia */
        }

        .dropdown-item.active,
        .dropdown-item.active:hover,
        .dropdown-item.active:focus,
        .dropdown-item.bg-success:hover {
            background-color: var(--color-secondary) !important;
            /* #018790 */
            color: white !important;
        }

        @yield('css')
    </style>
    @stack('styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-dark">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ route('videos.index') }}" class="nav-link">Videos</a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- Organization Dropdown (solo si tiene múltiples orgs) -->
                @php
                    $userOrganizations = auth()->user()->organizations;
                    $currentOrg = auth()->user()->currentOrganization();
                @endphp
                @if ($userOrganizations->count() > 1)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                            <i class="fas fa-building mr-1"></i>
                            {{ $currentOrg ? Str::limit($currentOrg->name, 15) : 'Sin org' }}
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            @foreach ($userOrganizations as $org)
                                <form action="{{ route('set-organization', $org) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit"
                                        class="dropdown-item {{ $currentOrg && $currentOrg->id === $org->id ? 'active bg-success' : '' }}">
                                        @if ($currentOrg && $currentOrg->id === $org->id)
                                            <i class="fas fa-check mr-2"></i>
                                        @else
                                            <i class="fas fa-building mr-2"></i>
                                        @endif
                                        {{ $org->name }}
                                        <small class="text-muted ml-2">({{ ucfirst($org->pivot->role) }})</small>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </li>
                @elseif($currentOrg)
                    <li class="nav-item">
                        <span class="nav-link text-light">
                            <i class="fas fa-building mr-1"></i>
                            {{ Str::limit($currentOrg->name, 20) }}
                        </span>
                    </li>
                @endif

                <!-- Notifications Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
                        <i class="far fa-bell"></i>
                        @if (auth()->user()->unreadNotifications->count() > 0)
                            <span class="badge badge-danger navbar-badge">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item dropdown-header">
                            {{ auth()->user()->unreadNotifications->count() }} Notificaciones no leídas
                        </span>
                        <div class="dropdown-divider"></div>

                        @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                            <a href="{{ route('videos.show', $notification->data['video_id']) }}?notification_id={{ $notification->id }}"
                                class="dropdown-item">
                                <i class="fas fa-at mr-2 text-primary"></i>
                                <strong>{{ $notification->data['mentioned_by_name'] }}</strong> te mencionó
                                <span class="float-right text-muted text-sm">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                                <p class="text-sm text-muted mb-0 mt-1">
                                    {{ Str::limit($notification->data['comment_text'], 50) }}
                                </p>
                            </a>
                            <div class="dropdown-divider"></div>
                        @empty
                            <div class="dropdown-item text-center text-muted">
                                <i class="fas fa-check-circle mr-1"></i>
                                No tienes notificaciones nuevas
                            </div>
                            <div class="dropdown-divider"></div>
                        @endforelse

                        @if (auth()->user()->unreadNotifications->count() > 0)
                            <a href="#" class="dropdown-item dropdown-footer"
                                onclick="event.preventDefault(); markAllNotificationsRead();">
                                Marcar todas como leídas
                            </a>
                        @endif
                    </div>
                </li>

                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown"
                        role="button" data-toggle="dropdown">
                        @if (Auth::user()->profile && Auth::user()->profile->avatar)
                            <img src="{{ asset('storage/' . Auth::user()->profile->avatar) }}" alt="Avatar"
                                class="img-circle mr-2" style="width: 28px; height: 28px; object-fit: cover;">
                        @else
                            <i class="fas fa-user mr-2"></i>
                        @endif
                        {{ Auth::user()->name }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="fas fa-user"></i> Perfil
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="{{ route('videos.index') }}" class="brand-link d-flex justify-content-center py-3">
                <img src="{{ $orgLogo }}" alt="{{ $orgName }} Logo" class="brand-logo-full"
                    style="width: 120px; height: auto; object-fit: contain;">
                <img src="{{ asset('favicon.png') }}" alt="{{ $orgName }}" class="brand-logo-mini"
                    style="width: 36px; height: 36px; object-fit: contain; display: none;">
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- User panel -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        @if (Auth::user()->profile && Auth::user()->profile->avatar)
                            <img src="{{ asset('storage/' . Auth::user()->profile->avatar) }}" alt="Avatar"
                                class="img-circle elevation-2" style="width: 34px; height: 34px; object-fit: cover;">
                        @else
                            <i class="fas fa-user-circle fa-2x text-light"></i>
                        @endif
                    </div>
                    <div class="info">
                        <a href="{{ route('profile.show') }}" class="text-light text-decoration-none">
                            {{ Auth::user()->name }}
                        </a>
                        <div>
                            <small class="text-muted">{{ ucfirst(Auth::user()->role) }}</small>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                        @if (in_array(Auth::user()->role, ['analista', 'entrenador']))
                            <li class="nav-item">
                                <a href="{{ route('videos.create') }}"
                                    class="nav-link {{ request()->routeIs('videos.create') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>Subir Video</p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->role !== 'jugador' && Auth::user()->role !== 'director_club')
                            <li class="nav-item">
                                <a href="{{ route('videos.index') }}"
                                    class="nav-link {{ request()->routeIs('videos.index') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>Videos del Equipo</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('my-videos') }}"
                                    class="nav-link {{ request()->routeIs('my-videos') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-circle"></i>
                                    <p>Mis Videos</p>
                                    @if (auth()->user()->pendingAssignments()->count() > 0)
                                        <span
                                            class="badge badge-warning navbar-badge">{{ auth()->user()->pendingAssignments()->count() }}</span>
                                    @endif
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->role === 'director_club')
                            <li class="nav-item">
                                <a href="{{ route('videos.index') }}"
                                    class="nav-link {{ request()->routeIs('videos.index') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>Videos del Equipo</p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->role === 'jugador')
                            <li class="nav-item">
                                <a href="{{ route('my-videos') }}"
                                    class="nav-link {{ request()->routeIs('my-videos') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>Mis Videos</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('videos.index') }}"
                                    class="nav-link {{ request()->routeIs('videos.index') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Videos del Equipo</p>
                                </a>
                            </li>
                            @php
                                $activePeriod = \App\Models\EvaluationPeriod::getActive();
                                $canEvaluate = $activePeriod && $activePeriod->isOpen();
                            @endphp
                            @if ($canEvaluate)
                                <li class="nav-item">
                                    <a href="{{ route('evaluations.index') }}"
                                        class="nav-link {{ request()->routeIs('evaluations.index') || request()->routeIs('evaluations.wizard') || request()->routeIs('evaluations.store') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-clipboard-check"></i>
                                        <p>Evaluación de Jugadores</p>
                                    </a>
                                </li>
                            @endif
                            <li class="nav-item">
                                <a href="{{ route('evaluations.dashboard') }}"
                                    class="nav-link {{ request()->routeIs('evaluations.dashboard') || request()->routeIs('evaluations.show') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-chart-line"></i>
                                    <p>Mis Resultados</p>
                                </a>
                            </li>
                            <!-- Funcionalidades Futuras -->
                            <li class="nav-item">
                                <a href="#" class="nav-link upcoming-feature" data-toggle="modal"
                                    data-target="#upcomingFeatureModal" data-feature="Jugadas">
                                    <i class="nav-icon fas fa-football-ball"></i>
                                    <p>
                                        Jugadas
                                        <span class="badge badge-success right">Próximamente</span>
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link upcoming-feature" data-toggle="modal"
                                    data-target="#upcomingFeatureModal" data-feature="Cuota Club">
                                    <i class="nav-icon fas fa-money-bill-wave"></i>
                                    <p>
                                        Cuota Club
                                        <span class="badge badge-success right">Próximamente</span>
                                    </p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->role === 'entrenador' || Auth::user()->role === 'analista')
                            <li class="nav-item">
                                <a href="{{ route('coach.users') }}"
                                    class="nav-link {{ request()->routeIs('coach.users') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Jugadores</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('evaluations.dashboard') }}"
                                    class="nav-link {{ request()->routeIs('evaluations.dashboard') || request()->routeIs('evaluations.show') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-chart-bar"></i>
                                    <p>Resultados de Evaluaciones</p>
                                </a>
                            </li>
                        @endif

                        @if (in_array(Auth::user()->role, ['analista', 'entrenador']))
                            <li class="nav-header">ADMINISTRACIÓN</li>
                            <li class="nav-item">
                                <a href="{{ route('admin.index') }}"
                                    class="nav-link {{ request()->routeIs('admin.index') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-tools"></i>
                                    <p>Mantenedor</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.organization') }}"
                                    class="nav-link {{ request()->routeIs('admin.organization') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-plus"></i>
                                    <p>Invitar Jugadores</p>
                                </a>
                            </li>
                            <!-- Funcionalidades Futuras -->
                            <li class="nav-item">
                                <a href="#" class="nav-link upcoming-feature" data-toggle="modal"
                                    data-target="#upcomingFeatureModal" data-feature="Crear Jugadas">
                                    <i class="nav-icon fas fa-draw-polygon"></i>
                                    <p>
                                        Crear Jugadas
                                        <span class="badge badge-success right">Próximamente</span>
                                    </p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->role === 'analista')
                            <!-- Gestión de Pagos (Solo Analistas) -->
                            <li class="nav-item">
                                <a href="#" class="nav-link upcoming-feature" data-toggle="modal"
                                    data-target="#upcomingFeatureModal" data-feature="Gestión de Pagos">
                                    <i class="nav-icon fas fa-credit-card"></i>
                                    <p>
                                        Gestión de Pagos
                                        <span class="badge badge-success right">Próximamente</span>
                                    </p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->isSuperAdmin())
                            <li class="nav-header text-danger">SUPER ADMIN</li>
                            <li class="nav-item">
                                <a href="{{ route('super-admin.dashboard') }}"
                                    class="nav-link {{ request()->routeIs('super-admin.dashboard') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-shield-alt text-danger"></i>
                                    <p>Panel Super Admin</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('super-admin.organizations') }}"
                                    class="nav-link {{ request()->routeIs('super-admin.organizations*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-building text-danger"></i>
                                    <p>Organizaciones</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('super-admin.users') }}"
                                    class="nav-link {{ request()->routeIs('super-admin.users') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users-cog text-danger"></i>
                                    <p>Todos los Usuarios</p>
                                </a>
                            </li>
                        @endif

                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                @yield('breadcrumbs')
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    @yield('main_content')
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            RugbyHub - Sistema de Análisis de Video
            <div class="float-right d-none d-sm-inline-block">
                <b>Versión</b> 1.0.0
            </div>
        </footer>
    </div>

    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Modal de Funcionalidad Próximamente -->
    <div class="modal fade" id="upcomingFeatureModal" tabindex="-1" role="dialog"
        aria-labelledby="upcomingFeatureModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%); color: white;">
                    <h5 class="modal-title" id="upcomingFeatureModalLabel">
                        <i class="fas fa-rocket"></i> Funcionalidad en Desarrollo
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-hammer fa-4x text-success"></i>
                    </div>
                    <h4 class="mb-3" id="featureName">Funcionalidad</h4>
                    <p class="text-muted mb-4" id="featureDescription">
                        Esta funcionalidad está en desarrollo y estará disponible próximamente.
                    </p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Nota:</strong> Estamos trabajando para traerte las mejores herramientas de análisis
                        rugby.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-rugby" data-dismiss="modal">
                        <i class="fas fa-check"></i> Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    @yield('js')
    @stack('scripts')

    <!-- Session Expiry Handler -->
    <script>
        $(document).ready(function() {
            // Global AJAX error handler for session expiry
            $(document).ajaxError(function(event, xhr, settings, thrownError) {
                if (xhr.status === 419) {
                    // Page expired (CSRF token expired)
                    handleSessionExpiry();
                } else if (xhr.status === 401) {
                    // Unauthorized (session expired)
                    handleSessionExpiry();
                }
            });

            // Handle form submissions with expired CSRF tokens
            $(document).on('submit', 'form', function(e) {
                const form = $(this);
                const csrfToken = $('meta[name="csrf-token"]').attr('content');

                // Check if form has CSRF token and if it's valid
                const formCsrfToken = form.find('input[name="_token"]').val();
                if (formCsrfToken && formCsrfToken !== csrfToken) {
                    e.preventDefault();
                    handleSessionExpiry();
                    return false;
                }
            });

            function handleSessionExpiry() {
                // Store current page URL to redirect back after login
                const currentUrl = window.location.href;
                const isVideoPage = currentUrl.includes('/videos/') && currentUrl.includes('/show');

                if (isVideoPage) {
                    // For video pages, store the video URL and timestamp
                    const video = document.getElementById('rugbyVideo');
                    const currentTime = video ? video.currentTime : 0;

                    localStorage.setItem('rugby_return_url', currentUrl);
                    localStorage.setItem('rugby_video_time', currentTime);

                    // Show user-friendly message
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Tu sesión ha expirado. Serás redirigido al login.', 'Sesión Expirada');
                    } else {
                        alert('Tu sesión ha expirado. Serás redirigido al login.');
                    }

                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                } else {
                    // For other pages, just store the URL
                    localStorage.setItem('rugby_return_url', currentUrl);

                    if (typeof toastr !== 'undefined') {
                        toastr.info('Tu sesión ha expirado. Redirigiendo...', 'Sesión Expirada');
                    } else {
                        alert('Tu sesión ha expirado. Serás redirigido al login.');
                    }

                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 1500);
                }
            }

            // Check if user just logged in and should return to a stored page
            @auth
            const returnUrl = localStorage.getItem('rugby_return_url');
            const videoTime = localStorage.getItem('rugby_video_time');

            if (returnUrl && returnUrl !== window.location.href) {
                // Only redirect if this is the dashboard or login success page
                const currentPath = window.location.pathname;
                if (currentPath === '/home' || currentPath === '/dashboard' || currentPath === '/') {
                    localStorage.removeItem('rugby_return_url');

                    if (videoTime) {
                        localStorage.removeItem('rugby_video_time');
                        // Add video time as URL parameter
                        const separator = returnUrl.includes('?') ? '&' : '?';
                        window.location.href = returnUrl + separator + 't=' + Math.floor(videoTime);
                    } else {
                        window.location.href = returnUrl;
                    }
                }
            }
        @endauth
        });

        // Upcoming Features Modal Handler
        $('.upcoming-feature').on('click', function(e) {
            e.preventDefault();
            const featureName = $(this).data('feature');

            // Definir descripciones para cada funcionalidad
            const featureDescriptions = {
                'Jugadas': 'Accede a un catálogo de jugadas de rugby predefinidas y personalizadas para mejorar tu comprensión táctica del juego.',
                'Cuota Club': 'Consulta el estado de tus cuotas mensuales, historial de pagos y mantente al día con tus compromisos con el club.',
                'Gestión de Pagos': 'Administra las cuotas de todos los jugadores, genera reportes de pagos y envía recordatorios automáticos.',
                'Crear Jugadas': 'Diseña y comparte jugadas personalizadas usando un editor visual interactivo con diagramas de campo.'
            };

            // Actualizar el modal
            $('#featureName').text(featureName);
            $('#featureDescription').text(featureDescriptions[featureName] ||
                'Esta funcionalidad está en desarrollo y estará disponible próximamente.');
        });

        // Marcar todas las notificaciones como leídas
        function markAllNotificationsRead() {
            $.ajax({
                url: '/notifications/mark-all-read',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Recargar la página para actualizar el contador
                        window.location.reload();
                    }
                },
                error: function() {
                    alert('Error al marcar notificaciones como leídas');
                }
            });
        }

        // Marcar notificación como leída al hacer click en el enlace
        $(document).on('click', 'a[href*="notification_id"]', function() {
            const url = new URL(this.href);
            const notificationId = url.searchParams.get('notification_id');

            if (notificationId) {
                $.ajax({
                    url: '/notifications/' + notificationId + '/mark-read',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    async: false // Esperar a que se marque antes de navegar
                });
            }
        });
    </script>
</body>

</html>

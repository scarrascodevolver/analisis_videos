<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $currentOrganization = auth()->check() ? auth()->user()->currentOrganization() : null;
        $orgName = $currentOrganization ? $currentOrganization->name : 'Rugby Key Performance';
        $logoV   = filemtime(public_path('logo.png'))   ?: time();
        $faviconV = filemtime(public_path('favicon.png')) ?: time();
        $orgLogo =
            $currentOrganization && $currentOrganization->logo_path
                ? asset('storage/' . $currentOrganization->logo_path)
                : asset('logo.png') . '?v=' . $logoV;
        $orgFavicon =
            $currentOrganization && $currentOrganization->logo_path
                ? asset('storage/' . $currentOrganization->logo_path)
                : asset('favicon.png') . '?v=' . $faviconV;
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
           FIX: Prevent horizontal overflow on mobile
           ======================================== */
        html, body {
            overflow-x: hidden;
            max-width: 100%;
        }

        /* Fix dropdown-menu-lg causing overflow on small screens */
        @media (max-width: 350px) {
            .dropdown-menu-lg {
                min-width: auto !important;
                max-width: calc(100vw - 20px) !important;
            }
        }

        /* Fix toast animations causing overflow */
        #toast-container {
            max-width: calc(100vw - 40px);
        }

        /* Ensure wrapper doesn't overflow */
        .wrapper {
            overflow-x: hidden;
        }

        /* Fix content-wrapper on mobile */
        .content-wrapper {
            overflow-x: hidden;
        }

        /* User dropdown responsive on mobile */
        @media (max-width: 576px) {
            .user-dropdown-menu {
                position: absolute;
                right: 0;
                left: auto;
                min-width: 150px;
            }

            /* Ensure all navbar dropdowns stay within viewport */
            .navbar .dropdown-menu {
                max-width: calc(100vw - 20px);
            }
        }

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
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .nav-sidebar .nav-link {
            color: #c2c7d0;
            font-size: 0.78rem;
            padding: 0.4rem 0.7rem;
        }

        .nav-sidebar .nav-link:hover {
            background-color: var(--color-secondary);
            color: white;
        }

        .nav-sidebar .nav-link.active {
            background-color: var(--color-accent) !important;
            color: white !important;
        }

        .nav-sidebar .nav-icon {
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }

        .nav-header {
            font-size: 0.6rem;
            padding: 0.35rem 0.7rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .brand-link {
            background-color: var(--color-primary) !important;
            color: white !important;
            border-bottom: 1px solid var(--color-secondary);
            padding: 0.6rem 0.7rem;
        }

        .brand-link .brand-image {
            max-height: 40px;
            border-radius: 8px;
            filter: drop-shadow(0 0 6px rgba(0, 183, 181, 0.5));
        }

        /* Logo switching for collapsed sidebar */
        .brand-logo-full { display: block; }
        .brand-logo-mini { display: none !important; }
        .sidebar-collapse .brand-logo-full { display: none !important; }
        .sidebar-collapse .brand-logo-mini { display: block !important; }

        .user-panel {
            padding: 0.6rem 0.7rem;
        }

        .user-panel .info {
            color: #c2c7d0;
            font-size: 0.78rem;
        }

        .user-panel .image {
            width: 2rem;
            height: 2rem;
        }

        .sidebar {
            flex: 1 1 auto;
            overflow-y: auto;
            padding-bottom: 16px;
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
                <!-- Organization Dropdown (solo si tiene múltiples orgs o es super admin) -->
                @php
                    $isSuperAdmin = auth()->user()->isSuperAdmin();
                    // Super admins ven TODAS las organizaciones
                    $userOrganizations = $isSuperAdmin
                        ? \App\Models\Organization::where('is_active', true)->orderBy('name')->get()
                        : auth()->user()->organizations;
                    $currentOrg = auth()->user()->currentOrganization();
                @endphp
                @if ($userOrganizations->count() > 1 || $isSuperAdmin)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                            <i class="fas fa-building mr-1"></i>
                            {{ $currentOrg ? Str::limit($currentOrg->name, 15) : 'Sin org' }}
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" style="max-height: 400px; overflow-y: auto;">
                            @if($isSuperAdmin)
                                <span class="dropdown-header text-danger"><i class="fas fa-shield-alt mr-1"></i>Super Admin - Todas las Orgs</span>
                                <div class="dropdown-divider"></div>
                            @endif
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
                                        @if(!$isSuperAdmin && isset($org->pivot))
                                            <small class="text-muted ml-2">({{ ucfirst($org->pivot->role) }})</small>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </li>
                @elseif($currentOrg)
                    <li class="nav-item">
                        <span class="nav-link text-light">
                            <i class="fas fa-building mr-1"></i>
                            {{-- Desktop: nombre completo --}}
                            <span class="d-none d-md-inline">{{ $currentOrg->name }}</span>
                            {{-- Móvil: cortado como dropdown --}}
                            <span class="d-inline d-md-none">{{ Str::limit($currentOrg->name, 15) }}</span>
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
                                class="img-circle" style="width: 28px; height: 28px; object-fit: cover;">
                        @else
                            <i class="fas fa-user"></i>
                        @endif
                        <span class="d-none d-md-inline ml-2">{{ Auth::user()->name }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right user-dropdown-menu">
                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="fas fa-user"></i> Perfil
                        </a>
                        <a class="dropdown-item" href="{{ route('profile.password') }}">
                            <i class="fas fa-key"></i> Cambiar Contraseña
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
            <a href="{{ route('videos.index') }}" class="brand-link d-flex justify-content-center py-2">
                <img src="{{ $orgLogo }}" alt="{{ $orgName }} Logo" class="brand-logo-full"
                    style="width: 120px; height: auto; object-fit: contain; border-radius: 8px; filter: drop-shadow(0 0 6px rgba(0,183,181,0.5));">
                <img src="{{ asset('favicon.png') }}" alt="{{ $orgName }}" class="brand-logo-mini"
                    style="width: 36px; height: 36px; object-fit: contain; display: none;">
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- User panel (oculto para analistas - ya está en navbar) -->
                @if(Auth::user()->role !== 'analista')
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
                @endif

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
                                    <p>{{ auth()->user()->currentOrganization()?->isClub() ? 'Videos del Equipo' : 'Videos' }}</p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->role === 'director_club')
                            <li class="nav-item">
                                <a href="{{ route('videos.index') }}"
                                    class="nav-link {{ request()->routeIs('videos.index') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>{{ auth()->user()->currentOrganization()?->isClub() ? 'Videos del Equipo' : 'Videos' }}</p>
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
                                    <p>{{ auth()->user()->currentOrganization()?->isClub() ? 'Videos del Equipo' : 'Videos' }}</p>
                                </a>
                            </li>
                            <!-- Ver Jugadas (acceso para jugadores) -->
                            <li class="nav-item">
                                <a href="{{ route('jugadas.index') }}" class="nav-link {{ request()->routeIs('jugadas.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-football-ball"></i>
                                    <p>Jugadas</p>
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
                            <!-- Editor de Jugadas -->
                            <li class="nav-item">
                                <a href="{{ route('jugadas.index') }}" class="nav-link {{ request()->routeIs('jugadas.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-chalkboard-teacher"></i>
                                    <p>
                                        Crear Jugadas
                                        <span class="badge badge-info right">Beta</span>
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
                            <li class="nav-item">
                                <a href="{{ route('super-admin.storage') }}"
                                    class="nav-link {{ request()->routeIs('super-admin.storage') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-database text-danger"></i>
                                    <p>Almacenamiento</p>
                                </a>
                            </li>
                        @endif

                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper" style="padding-top: 15px;">
            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    @yield('main_content')
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            Rugby Key Performance - Sistema de Análisis de Video
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

    <!-- Toast Container Global -->
    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <!-- Toast System -->
    <style>
        @keyframes toastSlideIn {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes toastSlideOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(100px); }
        }
    </style>
    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const bgColor = type === 'success' ? '#00B7B5' : (type === 'error' ? '#dc3545' : '#ffc107');
            const icon = type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : 'info-circle');

            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.style.cssText = `
                background: ${bgColor};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                margin-bottom: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                display: flex;
                align-items: center;
                gap: 10px;
                animation: toastSlideIn 0.3s ease;
                max-width: 350px;
            `;
            toast.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;

            container.appendChild(toast);

            // Auto remove after 4 seconds
            setTimeout(() => {
                toast.style.animation = 'toastSlideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Show toast for session messages
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast(@json(session('success')), 'success');
            @endif
            @if(session('error'))
                showToast(@json(session('error')), 'error');
            @endif
            @if(session('warning'))
                showToast(@json(session('warning')), 'warning');
            @endif
            @if(session('info'))
                showToast(@json(session('info')), 'info');
            @endif
        });
    </script>
@auth
@php
    $currentOrg = auth()->user()->currentOrganization();
    $isOrgAdmin  = $currentOrg && auth()->user()->organizations()
        ->wherePivot('is_org_admin', true)
        ->where('organizations.id', $currentOrg->id)
        ->exists();

    $needsOnboarding = false;
    if ($currentOrg && !$currentOrg->onboarding_completed && $isOrgAdmin) {
        if ($currentOrg->isClub()) {
            $needsOnboarding = \App\Models\Category::where('organization_id', $currentOrg->id)->count() === 0;
        } else {
            $needsOnboarding = \App\Models\Tournament::where('organization_id', $currentOrg->id)->count() === 0;
        }
    }
    $showOnboarding = $needsOnboarding;
@endphp

@if($showOnboarding)
<div class="modal fade" id="onboardingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#005461;color:white;">
                <h5 class="modal-title">
                    <i class="fas fa-rocket mr-2"></i>
                    ¡Bienvenido a RugbyKP! Configurá {{ $currentOrg->name }} en 1 minuto
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="onboardingForm" action="{{ route('onboarding.complete') }}" method="POST">
                    @csrf
                    @if($currentOrg->isClub())
                        <p class="text-muted mb-3">Seleccioná las categorías de tu club. Podés agregar más después.</p>
                        <div class="row">
                            @foreach(['Adulta', 'Juveniles', 'Femenino', 'M20', 'M18', 'M16'] as $cat)
                            <div class="col-md-4 mb-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input"
                                           id="cat_{{ Str::slug($cat) }}"
                                           name="categories[]"
                                           value="{{ $cat }}"
                                           {{ in_array($cat, ['Adulta', 'Juveniles', 'Femenino']) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="cat_{{ Str::slug($cat) }}">
                                        {{ $cat }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="form-group mt-2">
                            <label class="small text-muted">¿Otra categoría?</label>
                            <input type="text" id="extraCategory" class="form-control form-control-sm"
                                   placeholder="Ej: M14, Seven, etc." style="max-width:250px;">
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addExtraCategory()">
                                <i class="fas fa-plus mr-1"></i> Agregar
                            </button>
                            <div id="extraCategories"></div>
                        </div>
                    @else
                        <p class="text-muted mb-3">¿Qué torneo o liga vas a analizar primero?</p>
                        <div class="form-group">
                            <label class="font-weight-bold">Nombre del torneo / liga</label>
                            <input type="text" name="tournament_name" class="form-control"
                                   placeholder="Ej: Torneo de la URBA, Liga Nacional 2026..."
                                   required>
                            <small class="text-muted">Podés crear más torneos después desde el menú.</small>
                        </div>
                    @endif
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link text-muted" data-dismiss="modal">
                    Ahora no
                </button>
                <button type="submit" form="onboardingForm" class="btn btn-success btn-lg">
                    <i class="fas fa-check mr-2"></i>Guardar y empezar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var key = 'onboarding_dismissed_{{ $currentOrg->id }}_' + new Date().toDateString();
    if (!localStorage.getItem(key)) {
        $('#onboardingModal').modal('show');
    }
    $('#onboardingModal').on('hide.bs.modal', function() {
        localStorage.setItem(key, '1');
    });
});

function addExtraCategory() {
    var val = document.getElementById('extraCategory').value.trim();
    if (!val) return;
    var slug = val.toLowerCase().replace(/\s+/g, '-');
    var html = '<div class="custom-control custom-checkbox mt-1" id="extra_' + slug + '">' +
        '<input type="checkbox" class="custom-control-input" id="chk_' + slug + '" name="categories[]" value="' + val + '" checked>' +
        '<label class="custom-control-label" for="chk_' + slug + '">' + val + '</label>' +
        '</div>';
    document.getElementById('extraCategories').insertAdjacentHTML('beforeend', html);
    document.getElementById('extraCategory').value = '';
}
</script>
@endif
@endauth
</body>

</html>

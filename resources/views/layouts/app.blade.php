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
        $faviconV = filemtime(public_path('favicon.ico')) ?: time();
        $orgLogo =
            $currentOrganization && $currentOrganization->logo_path
                ? asset('storage/' . $currentOrganization->logo_path)
                : asset('logo.png') . '?v=' . $logoV;
        $orgFavicon =
            $currentOrganization && $currentOrganization->logo_path
                ? asset('storage/' . $currentOrganization->logo_path)
                : asset('favicon.ico') . '?v=' . $faviconV;
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
            --color-secondary: #4A6274;
            --color-accent: #b8860b;
            --color-sidebar: #0d1117;
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
            background-color: var(--color-sidebar) !important;
            border-bottom: 1px solid rgba(212, 160, 23, 0.15);
        }

        .main-sidebar {
            background-color: var(--color-sidebar) !important;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .nav-sidebar .nav-link,
        .sidebar-dark-primary .nav-sidebar .nav-link,
        .sidebar-dark-primary .nav-sidebar .nav-treeview .nav-link {
            color: #00B7B5 !important;
            font-size: 0.78rem;
            padding: 0.4rem 0.7rem;
        }

        .nav-sidebar .nav-link p,
        .nav-sidebar .nav-link > p,
        .sidebar-dark-primary .nav-sidebar .nav-link p,
        .sidebar-dark-primary .nav-sidebar .nav-treeview .nav-link p {
            color: #00B7B5 !important;
        }

        .nav-sidebar .nav-link.active p,
        .nav-sidebar .nav-link.active > p,
        .sidebar-dark-primary .nav-sidebar .nav-link.active p {
            color: #fff !important;
        }

        .nav-sidebar .nav-link:hover,
        .sidebar-dark-primary .nav-sidebar .nav-link:hover {
            background-color: var(--color-secondary);
            color: #00d4d2 !important;
        }

        .nav-sidebar .nav-link:hover p,
        .sidebar-dark-primary .nav-sidebar .nav-link:hover p {
            color: #00d4d2 !important;
        }

        .nav-sidebar .nav-link.active,
        .sidebar-dark-primary .nav-sidebar .nav-link.active {
            background-color: var(--color-primary) !important;
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
            background-color: var(--color-sidebar) !important;
            color: white !important;
            border-bottom: 1px solid rgba(212, 160, 23, 0.2);
            padding: 0.6rem 0.7rem;
        }

        .brand-link .brand-image {
            max-height: 40px;
            filter: drop-shadow(0 0 6px rgba(212, 160, 23, 0.5));
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

        /* ── Notification dropdown ───────────────────────────── */
        .notif-dropdown {
            min-width: 300px;
            max-width: 320px;
            padding: 0;
            background: #1a1a2e;
            border: 1px solid rgba(0,183,181,.25);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,.5);
            overflow: hidden;
        }
        .notif-header {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            font-size: .75rem;
            font-weight: 600;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: .05em;
            background: rgba(0,84,97,.2);
            border-bottom: 1px solid rgba(0,183,181,.15);
        }
        .notif-count-badge {
            margin-left: auto;
            background: #e74c3c;
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 10px;
        }
        .notif-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 12px;
            color: #ccc;
            text-decoration: none;
            font-size: .78rem;
            border-bottom: 1px solid rgba(255,255,255,.05);
            transition: background .15s;
        }
        .notif-item:hover {
            background: rgba(0,183,181,.08);
            color: #fff;
            text-decoration: none;
        }
        .notif-icon {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .7rem;
            margin-top: 1px;
        }
        .notif-body {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .notif-text {
            line-height: 1.3;
            color: #ddd;
        }
        .notif-text strong { color: #fff; }
        .notif-text em { color: #00B7B5; font-style: normal; }
        .notif-time {
            font-size: .68rem;
            color: #666;
        }
        .notif-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 14px 12px;
            font-size: .78rem;
            color: #666;
        }
        .notif-footer {
            padding: 7px 12px;
            background: rgba(0,84,97,.15);
            border-top: 1px solid rgba(0,183,181,.12);
            text-align: center;
        }
        .notif-footer a {
            font-size: .72rem;
            color: #00B7B5;
            text-decoration: none;
        }
        .notif-footer a:hover { color: #fff; }

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
            /* #4A6274 */
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
                    $isOrgManager = auth()->user()->isOrgManager();
                    // Super admins ven TODAS las organizaciones
                    // Org managers ven solo las que crearon
                    if ($isSuperAdmin) {
                        $userOrganizations = \App\Models\Organization::where('is_active', true)->orderBy('name')->get();
                    } elseif ($isOrgManager) {
                        $userOrganizations = \App\Models\Organization::where('is_active', true)->where('created_by', auth()->id())->orderBy('name')->get();
                    } else {
                        $userOrganizations = auth()->user()->organizations;
                    }
                    $currentOrg = auth()->user()->currentOrganization();
                @endphp
                @if ($userOrganizations->count() > 1 || $isSuperAdmin || $isOrgManager)
                    <li class="nav-item dropdown" id="navbarOrgDropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                            <i class="fas fa-building mr-1"></i>
                            {{ $currentOrg ? Str::limit($currentOrg->name, 15) : 'Sin org' }}
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" style="max-height:400px;overflow-y:auto;background:#1a1a1a;border:1px solid rgba(255,255,255,.12);">
                            <style>
                                #navbarOrgDropdown .dropdown-item { color:rgba(255,255,255,.8) !important; }
                                #navbarOrgDropdown .dropdown-item:hover,#navbarOrgDropdown .dropdown-item:focus { background:rgba(0,183,181,.15) !important;color:#fff !important; }
                                #navbarOrgDropdown .dropdown-header { color:rgba(255,255,255,.4) !important; }
                                #navbarOrgDropdown .dropdown-divider { border-color:rgba(255,255,255,.1) !important; }
                            </style>
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
                    <div class="dropdown-menu dropdown-menu-right notif-dropdown">
                        {{-- Header --}}
                        <div class="notif-header">
                            <i class="far fa-bell mr-1" style="color:#00B7B5;"></i>
                            <span>Notificaciones</span>
                            @if(auth()->user()->unreadNotifications->count() > 0)
                                <span class="notif-count-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
                            @endif
                        </div>

                        @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                            @php
                                $type = $notification->data['type'] ?? '';
                            @endphp
                            @if($type === 'tournament_join_request')
                                <a href="{{ route('tournaments.index') }}#pane-solicitudes"
                                   class="notif-item"
                                   onclick="markNotificationRead('{{ $notification->id }}')">
                                    <span class="notif-icon" style="background:rgba(255,193,7,.15);color:#ffc107;">
                                        <i class="fas fa-user-clock"></i>
                                    </span>
                                    <span class="notif-body">
                                        <span class="notif-text"><strong>{{ Str::limit($notification->data['club_org_name'], 20) }}</strong> quiere unirse a <em>{{ Str::limit($notification->data['tournament_name'], 22) }}</em></span>
                                        <span class="notif-time">{{ $notification->created_at->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @elseif($type === 'video_shared')
                                <a href="{{ route('videos.show', $notification->data['video_id']) }}?notification_id={{ $notification->id }}"
                                   class="notif-item">
                                    <span class="notif-icon" style="background:rgba(0,183,181,.15);color:#00B7B5;">
                                        <i class="fas fa-share-alt"></i>
                                    </span>
                                    <span class="notif-body">
                                        <span class="notif-text"><strong>{{ Str::limit($notification->data['source_org_name'], 20) }}</strong> te envió: <em>{{ Str::limit($notification->data['video_title'], 25) }}</em></span>
                                        <span class="notif-time">{{ $notification->created_at->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @else
                                <a href="{{ route('videos.show', $notification->data['video_id']) }}?notification_id={{ $notification->id }}"
                                   class="notif-item">
                                    <span class="notif-icon" style="background:rgba(0,84,97,.3);color:#00B7B5;">
                                        <i class="fas fa-at"></i>
                                    </span>
                                    <span class="notif-body">
                                        <span class="notif-text"><strong>{{ Str::limit($notification->data['mentioned_by_name'], 20) }}</strong> te mencionó: <em>{{ Str::limit($notification->data['comment_text'], 30) }}</em></span>
                                        <span class="notif-time">{{ $notification->created_at->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @endif
                        @empty
                            <div class="notif-empty">
                                <i class="fas fa-check-circle" style="color:#00B7B5;opacity:.6;"></i>
                                <span>Todo al día</span>
                            </div>
                        @endforelse

                        @if(auth()->user()->unreadNotifications->count() > 0)
                            <div class="notif-footer">
                                <a href="#" onclick="event.preventDefault();markAllNotificationsRead();">
                                    <i class="fas fa-check-double mr-1"></i>Marcar todas como leídas
                                </a>
                            </div>
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
                    style="width: 120px; height: auto; object-fit: contain; filter: drop-shadow(0 0 6px rgba(212,160,23,0.5));">
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

                        {{-- Torneos: gestión para asociaciones --}}
                        @if (in_array(Auth::user()->role, ['analista', 'entrenador']) && auth()->user()->currentOrganization()?->isAsociacion())
                            <li class="nav-item">
                                <a href="{{ route('tournaments.index') }}"
                                    class="nav-link {{ request()->routeIs('tournaments.index') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-trophy"></i>
                                    <p>
                                        Torneos
                                        @php
                                            $pendingCount = \App\Models\TournamentRegistration::whereHas('tournament', fn($q) => $q->where('organization_id', auth()->user()->currentOrganization()->id))->where('status','pending')->count();
                                        @endphp
                                        @if($pendingCount > 0)
                                            <span class="badge badge-danger right">{{ $pendingCount }}</span>
                                        @endif
                                    </p>
                                </a>
                            </li>
                        @endif

                        {{-- Torneos disponibles: solo para clubs (buscar torneos de asociaciones) --}}
                        @if (in_array(Auth::user()->role, ['analista', 'entrenador']) && auth()->user()->currentOrganization()?->isClub())
                            <li class="nav-item">
                                <a href="{{ route('tournaments.explore') }}"
                                    class="nav-link {{ request()->routeIs('tournaments.explore') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-globe"></i>
                                    <p>Torneos Disponibles</p>
                                </a>
                            </li>
                        @endif

                        @if (in_array(Auth::user()->role, ['analista', 'entrenador']) || Auth::user()->isSuperAdmin())
                            @php
                                $__inviteOrg  = Auth::user()->currentOrganization();
                                $__inviteCode = $__inviteOrg?->invitation_code;
                                $__inviteUrl  = $__inviteCode ? url('/register?code=' . $__inviteCode) : null;
                            @endphp
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-toggle="modal" data-target="#quickInviteModal">
                                    <i class="nav-icon fas fa-user-plus"></i>
                                    <p style="font-weight:600;">Invitar Jugador</p>
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
                                    <i class="nav-icon fas fa-building"></i>
                                    <p>Mi Organización</p>
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
                        @elseif (Auth::user()->isOrgManager())
                            <li class="nav-header" style="color:#b8860b;">MIS ORGANIZACIONES</li>
                            <li class="nav-item">
                                <a href="{{ route('super-admin.dashboard') }}"
                                    class="nav-link {{ request()->routeIs('super-admin.dashboard') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-chart-bar" style="color:#b8860b;"></i>
                                    <p>Mi Panel</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('super-admin.organizations') }}"
                                    class="nav-link {{ request()->routeIs('super-admin.organizations*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-building" style="color:#b8860b;"></i>
                                    <p>Mis Clubes y Orgs</p>
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

    <!-- Modal Invitar Jugador -->
    @if (in_array(Auth::user()->role, ['analista', 'entrenador']) || Auth::user()->isSuperAdmin())
    @php
        $__org  = Auth::user()->currentOrganization();
        $__code = $__org?->invitation_code;
        $__url  = $__code ? url('/register?code=' . $__code) : null;
    @endphp
    <div class="modal fade" id="quickInviteModal" tabindex="-1" role="dialog" aria-labelledby="quickInviteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:420px;">
            <div class="modal-content" style="background:#1a1a1a;border:1px solid #333;border-radius:8px;">
                <div class="modal-header" style="border-bottom:1px solid #333;padding:.75rem 1rem;">
                    <h5 class="modal-title" style="color:#fff;font-size:.95rem;font-weight:600;">
                        <i class="fas fa-user-plus mr-2" style="color:#00B7B5;"></i>Invitar Jugador
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" style="color:#aaa;">&times;</button>
                </div>
                <div class="modal-body" style="padding:1rem;">
                    @if($__code)
                        <p style="color:#aaa;font-size:.8rem;margin-bottom:1rem;">
                            Compartí el código o el link directo para que el jugador se registre en
                            <strong style="color:#fff;">{{ $__org->name }}</strong>.
                        </p>

                        {{-- Código --}}
                        <label style="color:#888;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;">Código de invitación</label>
                        <div class="input-group mb-3">
                            <input type="text" id="inviteCodeInput" class="form-control"
                                value="{{ $__code }}" readonly
                                style="background:#252525;border:1px solid #444;color:#fff;font-size:1.3rem;font-family:monospace;letter-spacing:.15em;font-weight:700;">
                            <div class="input-group-append">
                                <button class="btn" onclick="quickInviteCopy('inviteCodeInput','Código copiado')"
                                    style="background:#333;border:1px solid #444;color:#00B7B5;">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Link --}}
                        <label style="color:#888;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;">Link de registro directo</label>
                        <div class="input-group mb-3">
                            <input type="text" id="inviteLinkInput" class="form-control"
                                value="{{ $__url }}" readonly
                                style="background:#252525;border:1px solid #444;color:#ccc;font-size:.78rem;">
                            <div class="input-group-append">
                                <button class="btn" onclick="quickInviteCopy('inviteLinkInput','Link copiado')"
                                    style="background:#333;border:1px solid #444;color:#00B7B5;">
                                    <i class="fas fa-link"></i>
                                </button>
                            </div>
                        </div>

                        <div id="quickInviteToast" style="display:none;background:#005461;color:#fff;padding:.4rem .75rem;border-radius:4px;font-size:.8rem;text-align:center;"></div>
                    @else
                        <p style="color:#aaa;">No se encontró un código de invitación para tu organización.</p>
                    @endif
                </div>
                @if($__code && in_array(Auth::user()->role, ['analista', 'entrenador']))
                <div class="modal-footer" style="border-top:1px solid #333;padding:.6rem 1rem;">
                    <a href="{{ route('admin.organization') }}" class="btn btn-sm"
                        style="background:#333;color:#aaa;font-size:.78rem;">
                        <i class="fas fa-cog mr-1"></i> Gestionar código
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
    <script>
    function quickInviteCopy(inputId, msg) {
        var el = document.getElementById(inputId);
        if (!el) return;
        el.select(); el.setSelectionRange(0, 99999);
        try { document.execCommand('copy'); } catch(e) {}
        var toast = document.getElementById('quickInviteToast');
        if (toast) { toast.textContent = '✓ ' + msg; toast.style.display = 'block'; setTimeout(function(){ toast.style.display='none'; }, 2000); }
    }
    </script>
    @endif

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

        // Marcar una notificación individual como leída
        function markNotificationRead(id) {
            $.ajax({
                url: '/notifications/' + id + '/mark-read',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
            });
        }

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

            const bgColor = type === 'success' ? '#b8860b' : (type === 'error' ? '#dc3545' : '#ffc107');
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
        <div class="modal-content" style="background:#1a1a1a;border:1px solid rgba(255,255,255,.12);">
            <div class="modal-header" style="background:#005461;border-bottom:1px solid rgba(255,255,255,.1);">
                <h5 class="modal-title" style="color:#fff;">
                    <i class="fas fa-rocket mr-2"></i>
                    ¡Bienvenido a RugbyKP! Configurá {{ $currentOrg->name }} en 1 minuto
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="background:#1a1a1a;">
                <form id="onboardingForm" action="{{ route('onboarding.complete') }}" method="POST">
                    @csrf
                    @if($currentOrg->isClub())
                        <p style="color:rgba(255,255,255,.6);font-size:.88rem;" class="mb-3">Seleccioná las categorías de tu club. Podés agregar más después.</p>
                        <div class="row">
                            @foreach(['Adulta', 'Juveniles', 'Femenino', 'M20', 'M18', 'M16'] as $cat)
                            <div class="col-md-4 mb-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input"
                                           id="cat_{{ Str::slug($cat) }}"
                                           name="categories[]"
                                           value="{{ $cat }}"
                                           {{ in_array($cat, ['Adulta', 'Juveniles', 'Femenino']) ? 'checked' : '' }}>
                                    <label class="custom-control-label" style="color:rgba(255,255,255,.85);" for="cat_{{ Str::slug($cat) }}">
                                        {{ $cat }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="form-group mt-2">
                            <label class="small" style="color:rgba(255,255,255,.5);">¿Otra categoría?</label>
                            <input type="text" id="extraCategory" class="form-control form-control-sm"
                                   placeholder="Ej: M14, Seven, etc."
                                   style="max-width:250px;background:#111;border:1px solid #444;color:#fff;">
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addExtraCategory()">
                                <i class="fas fa-plus mr-1"></i> Agregar
                            </button>
                            <div id="extraCategories"></div>
                        </div>
                    @else
                        <p style="color:rgba(255,255,255,.6);font-size:.88rem;" class="mb-3">¿Qué torneo o liga vas a analizar primero?</p>
                        <div class="form-group">
                            <label style="color:rgba(255,255,255,.8);font-weight:600;">Nombre del torneo / liga <span class="text-danger">*</span></label>
                            <input type="text" id="ob-nt-name" class="form-control"
                                   placeholder="Ej: Torneo de la URBA, Liga Nacional 2026..."
                                   maxlength="255"
                                   style="background:#111;border:1px solid #444;color:#fff;">
                        </div>
                        <div class="form-group mb-0">
                            <label style="color:rgba(255,255,255,.8);font-weight:600;">Temporada <small style="color:rgba(255,255,255,.4);font-weight:400;">(opcional)</small></label>
                            <input type="text" id="ob-nt-season" class="form-control"
                                   placeholder="Ej: 2026" maxlength="20"
                                   style="background:#111;border:1px solid #444;color:#fff;">
                        </div>
                        <div id="ob-nt-error" class="text-danger small mt-2 d-none"></div>
                        <div id="ob-nt-warning" class="small mt-2 d-none" style="color:#f0ad4e;"><i class="fas fa-exclamation-triangle mr-1"></i><span id="ob-nt-warning-text"></span></div>
                        <small style="color:rgba(255,255,255,.4);" class="d-block mt-2">Podés crear más torneos después desde el menú.</small>
                    @endif
                </form>
            </div>
            <div class="modal-footer" style="background:#1a1a1a;border-top:1px solid rgba(255,255,255,.1);">
                <button type="button" class="btn btn-link" style="color:rgba(255,255,255,.4);" data-dismiss="modal">
                    Ahora no
                </button>
                @if($currentOrg->isClub())
                <button type="submit" form="onboardingForm" class="btn btn-success btn-lg">
                    <i class="fas fa-check mr-2"></i>Guardar y empezar
                </button>
                @else
                <button type="button" class="btn btn-success btn-lg" id="ob-nt-next-btn">
                    <i class="fas fa-arrow-right mr-2"></i>Siguiente
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

@if(!$currentOrg->isClub())
{{-- ── Onboarding Asociación: Modal 2 – Divisiones ─────────────────────── --}}
<div class="modal fade" id="obDivisionesModal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document" style="max-width:500px;">
        <div class="modal-content" style="background:#1a1a1a;border:1px solid rgba(255,255,255,.12);">
            <div class="modal-header" style="background:#005461;border-bottom:1px solid rgba(255,255,255,.1);">
                <h5 class="modal-title" style="color:#fff;">
                    <i class="fas fa-layer-group mr-2"></i>
                    Agregar divisiones al torneo
                </h5>
                <small id="ob-nd-tournament-name" class="ml-2" style="color:rgba(255,255,255,.7);"></small>
            </div>
            <div class="modal-body" style="background:#1a1a1a;">
                <p style="color:rgba(255,255,255,.5);font-size:.88rem;" class="mb-3">
                    Agregá las divisiones del torneo. Podés saltear este paso si no aplica.
                </p>

                {{-- Sugerencias rápidas --}}
                <div class="mb-3">
                    <div class="small font-weight-bold mb-2" style="color:rgba(255,255,255,.5);">Sugerencias rápidas:</div>
                    <div style="display:flex;flex-wrap:wrap;gap:7px;" id="ob-nd-chips">
                        @foreach(['Adulta','M18','M16','M14','M12','M10','M8','Seven','Femenino'] as $suggestion)
                        <button type="button" class="ob-nd-chip-btn"
                                data-name="{{ $suggestion }}"
                                style="background:transparent;border:1px solid rgba(0,183,181,.45);color:rgba(0,183,181,.9);border-radius:20px;padding:4px 14px;font-size:.82rem;cursor:pointer;transition:all .15s;">
                            {{ $suggestion }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Input personalizado --}}
                <div class="d-flex align-items-center mb-3" style="gap:8px;">
                    <input type="text" id="ob-nd-custom-input"
                           class="form-control form-control-sm"
                           placeholder="Otra división..."
                           style="background:#111;border:1px solid #444;color:#fff;">
                    <button type="button" id="ob-nd-add-btn"
                            style="background:rgba(0,183,181,.15);border:1px solid #00B7B5;color:#00B7B5;border-radius:4px;padding:5px 14px;font-size:.82rem;cursor:pointer;white-space:nowrap;">
                        <i class="fas fa-plus mr-1"></i> Agregar
                    </button>
                </div>

                {{-- Pills divisiones agregadas --}}
                <div id="ob-nd-added-pills" style="display:flex;flex-wrap:wrap;gap:7px;min-height:28px;"></div>
                <div id="ob-nd-div-error" class="text-danger small mt-1 d-none"></div>

                {{-- ¿Publicar torneo? --}}
                <div style="margin-top:16px;padding:12px 14px;background:rgba(255,255,255,.04);border-radius:6px;border:1px solid rgba(255,255,255,.08);">
                    <div style="font-size:.78rem;color:rgba(255,255,255,.5);font-weight:600;margin-bottom:10px;">
                        <i class="fas fa-globe mr-1"></i> ¿Los clubes pueden inscribirse?
                    </div>
                    <div style="display:flex;gap:8px;">
                        <label id="ob-nd-opt-private"
                               style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid rgba(0,183,181,.4);background:rgba(0,183,181,.08);flex:1;transition:all .15s;">
                            <input type="radio" name="ob-nd-visibility" value="private" checked style="accent-color:#00B7B5;">
                            <span style="font-size:.83rem;color:rgba(255,255,255,.8);">
                                <i class="fas fa-lock mr-1" style="color:rgba(255,255,255,.4);font-size:.8rem;"></i>Privado por ahora
                            </span>
                        </label>
                        <label id="ob-nd-opt-public"
                               style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid rgba(255,255,255,.1);background:transparent;flex:1;transition:all .15s;">
                            <input type="radio" name="ob-nd-visibility" value="public" style="accent-color:#00B7B5;">
                            <span style="font-size:.83rem;color:rgba(255,255,255,.8);">
                                <i class="fas fa-globe mr-1" style="color:rgba(0,183,181,.8);font-size:.8rem;"></i>Publicar ahora
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background:#1a1a1a;border-top:1px solid rgba(255,255,255,.1);justify-content:space-between;align-items:center;">
                <a href="#" id="ob-nd-skip-link" style="font-size:.8rem;color:rgba(255,255,255,.4);text-decoration:none;">
                    Continuar sin divisiones
                </a>
                <button type="button" id="ob-nd-continue-btn" class="btn btn-success">
                    <i class="fas fa-check mr-1"></i> Guardar y empezar
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
$(document).ready(function() {
    var key = 'onboarding_dismissed_{{ $currentOrg->id }}_' + new Date().toDateString();
    if (!localStorage.getItem(key)) {
        $('#onboardingModal').modal('show');
    }
    // Solo guardar dismissal si es club (asociación tiene flujo AJAX)
    @if($currentOrg->isClub())
    $('#onboardingModal').on('hide.bs.modal', function() {
        localStorage.setItem(key, '1');
    });
    @endif
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

@if(!$currentOrg->isClub())
// ── Onboarding Asociación: flujo 2 pasos ─────────────────────────────
(function () {
    var obTournamentId = null;
    var csrf = document.querySelector('meta[name=csrf-token]').content;

    // Paso 1: crear torneo
    var nextBtn = document.getElementById('ob-nt-next-btn');
    if (!nextBtn) return;

    nextBtn.addEventListener('click', function () {
        var name   = (document.getElementById('ob-nt-name').value || '').trim();
        var season = (document.getElementById('ob-nt-season').value || '').trim();
        var errEl  = document.getElementById('ob-nt-error');

        if (!name) {
            errEl.textContent = 'El nombre del torneo es obligatorio.';
            errEl.classList.remove('d-none');
            return;
        }
        errEl.classList.add('d-none');
        nextBtn.disabled = true;
        nextBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creando...';

        fetch('/api/tournaments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ name: name, season: season || null }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.id) {
                obTournamentId = data.id;
                if (data.already_exists) {
                    document.getElementById('ob-nt-warning-text').textContent = 'Ya existe un torneo con ese nombre. Podés continuar igual.';
                    document.getElementById('ob-nt-warning').classList.remove('d-none');
                }
                document.getElementById('ob-nd-tournament-name').textContent = '— ' + name;
                document.getElementById('ob-nd-added-pills').innerHTML = '';
                document.getElementById('ob-nd-custom-input').value = '';
                document.getElementById('ob-nd-div-error').classList.add('d-none');
                document.querySelectorAll('.ob-nd-chip-btn').forEach(function (c) {
                    c.disabled = false; c.style.opacity = '1';
                });
                $('#onboardingModal').one('hidden.bs.modal', function () {
                    setTimeout(function () {
                        // Reset visibility
                        document.querySelector('input[name="ob-nd-visibility"][value="private"]').checked = true;
                        document.getElementById('ob-nd-opt-private').style.cssText = 'display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid rgba(0,183,181,.4);background:rgba(0,183,181,.06);flex:1;transition:all .15s;';
                        document.getElementById('ob-nd-opt-public').style.cssText  = 'display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid #dee2e6;background:transparent;flex:1;transition:all .15s;';
                        $('#obDivisionesModal').modal('show');
                    }, 150);
                });
                $('#onboardingModal').modal('hide');
            } else {
                errEl.textContent = data.message || 'Error al crear el torneo.';
                errEl.classList.remove('d-none');
                nextBtn.disabled = false;
                nextBtn.innerHTML = '<i class="fas fa-arrow-right mr-2"></i>Siguiente';
            }
        })
        .catch(function () {
            errEl.textContent = 'Error de red. Intentá de nuevo.';
            errEl.classList.remove('d-none');
            nextBtn.disabled = false;
            nextBtn.innerHTML = '<i class="fas fa-arrow-right mr-2"></i>Siguiente';
        });
    });

    document.getElementById('ob-nt-name').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); nextBtn.click(); }
    });

    // Paso 2: divisiones
    function addObDivisionPill(divId, divName) {
        var pill = document.createElement('span');
        pill.className = 'div-pill';
        pill.dataset.divId = divId;
        pill.innerHTML = '<i class="fas fa-layer-group" style="font-size:.75rem;opacity:.7;"></i> ' + divName +
            ' <a href="#" class="div-pill-remove" onclick="(function(e,nm){e.preventDefault();' +
            'e.currentTarget.closest(\'.div-pill\').remove();' +
            'document.querySelectorAll(\'.ob-nd-chip-btn\').forEach(function(b){if(b.dataset.name===nm){b.disabled=false;b.style.opacity=\'1\';}});' +
            '})(event,\'' + divName.replace(/'/g, "\\'") + '\')">&times;</a>';
        document.getElementById('ob-nd-added-pills').appendChild(pill);
    }

    function submitObDivision(name, chipBtn) {
        if (!name || !obTournamentId) return;
        fetch('/api/tournaments/' + obTournamentId + '/divisions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ name: name }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) {
                addObDivisionPill(data.division.id, data.division.name);
                if (chipBtn) { chipBtn.disabled = true; chipBtn.style.opacity = '0.4'; }
                document.getElementById('ob-nd-custom-input').value = '';
                document.getElementById('ob-nd-div-error').classList.add('d-none');
            } else {
                var errEl = document.getElementById('ob-nd-div-error');
                errEl.textContent = data.error || 'Error al crear la división.';
                errEl.classList.remove('d-none');
            }
        });
    }

    document.querySelectorAll('.ob-nd-chip-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (this.disabled) return;
            submitObDivision(this.dataset.name, this);
        });
    });

    document.getElementById('ob-nd-add-btn').addEventListener('click', function () {
        var val = (document.getElementById('ob-nd-custom-input').value || '').trim();
        if (val) submitObDivision(val, null);
    });

    document.getElementById('ob-nd-custom-input').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('ob-nd-add-btn').click(); }
    });

    // Highlight visibility option
    document.querySelectorAll('input[name="ob-nd-visibility"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.getElementById('ob-nd-opt-private').style.cssText = 'display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid #dee2e6;background:transparent;flex:1;transition:all .15s;';
            document.getElementById('ob-nd-opt-public').style.cssText  = 'display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid #dee2e6;background:transparent;flex:1;transition:all .15s;';
            var activeId = this.value === 'private' ? 'ob-nd-opt-private' : 'ob-nd-opt-public';
            document.getElementById(activeId).style.cssText = 'display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid rgba(0,183,181,.4);background:rgba(0,183,181,.06);flex:1;transition:all .15s;';
        });
    });

    function finishObOnboarding() {
        var makePublic = document.querySelector('input[name="ob-nd-visibility"]:checked')?.value === 'public';
        var continueBtn = document.getElementById('ob-nd-continue-btn');
        if (continueBtn) { continueBtn.disabled = true; continueBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...'; }

        var doMarkComplete = function() {
            fetch('{{ route('onboarding.mark-complete') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                window.location.href = data.redirect || '/';
            })
            .catch(function () { window.location.href = '/'; });
        };

        if (makePublic && obTournamentId) {
            fetch('/api/tournaments/' + obTournamentId + '/toggle-public', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            }).finally(doMarkComplete);
        } else {
            doMarkComplete();
        }
    }

    document.getElementById('ob-nd-continue-btn').addEventListener('click', finishObOnboarding);
    document.getElementById('ob-nd-skip-link').addEventListener('click', function (e) {
        e.preventDefault();
        finishObOnboarding();
    });
})();
@endif
</script>
@endif
@endauth
</body>

</html>

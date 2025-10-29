<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page_title', 'Dashboard') - Los Troncos</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_lt.png') }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- AdminLTE CSS -->
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    <!-- Bebas Neue Font -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <!-- Fabric.js para anotaciones -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>

    <style>
        .rugby-green {
            background-color: #1e4d2b !important;
            color: white;
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

        .main-header.navbar {
            background-color: #1e4d2b !important;
        }

        .main-sidebar {
            background-color: #343a40 !important;
        }

        .nav-sidebar .nav-link {
            color: #c2c7d0;
        }

        .nav-sidebar .nav-link:hover {
            background-color: #1e4d2b;
            color: white;
        }

        .nav-sidebar .nav-link.active {
            background-color: #1e4d2b !important;
            color: white !important;
        }

        .brand-link {
            background-color: #1e4d2b !important;
            color: white !important;
            border-bottom: 1px solid #2d5a3a;
        }

        .user-panel .info {
            color: #c2c7d0;
        }

        .info-box-rugby {
            background: linear-gradient(45deg, #1e4d2b, #2d5a3a);
            color: white;
        }

        .info-box-rugby .info-box-text,
        .info-box-rugby .info-box-number {
            color: white;
        }

        .card-rugby {
            border-top: 3px solid #1e4d2b;
        }

        .small-box .icon {
            font-size: 70px;
            top: 10px;
            right: 15px;
            position: absolute;
            opacity: 0.3;
        }

        .content-wrapper {
            background-color: #f4f6f9;
        }

        .video-card {
            transition: all 0.3s;
        }

        .video-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Upcoming Features Styles */
        .upcoming-feature {
            opacity: 0.8;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upcoming-feature:hover {
            opacity: 1;
            background-color: rgba(30, 77, 43, 0.1) !important;
        }

        .upcoming-feature .badge {
            font-size: 0.65rem;
            padding: 2px 6px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Sobrescribir color azul de Bootstrap para enlaces */
        .breadcrumb-item a {
            color: #1e4d2b !important;
        }
        
        .breadcrumb-item a:hover {
            color: #2d5a3a !important;
            text-decoration: none;
        }
        
        /* Enlaces generales en verde */
        a {
            color: #1e4d2b;
        }
        
        a:hover {
            color: #2d5a3a;
            text-decoration: none;
        }

        @yield('css')
    </style>
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
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                        @if(Auth::user()->profile && Auth::user()->profile->avatar)
                            <img src="{{ asset('storage/' . Auth::user()->profile->avatar) }}"
                                 alt="Avatar"
                                 class="img-circle mr-2"
                                 style="width: 28px; height: 28px; object-fit: cover;">
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
            <a href="{{ route('videos.index') }}" class="brand-link">
                <img src="{{ asset('logo_lt.png') }}" alt="Los Troncos Logo" 
                     class="brand-image img-circle elevation-3" 
                     style="width: 33px; height: 33px; object-fit: cover;">
                <span class="brand-text" style="font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; letter-spacing: 3px; vertical-align: middle; margin-left: 10px;">Los Troncos</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- User panel -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        @if(Auth::user()->profile && Auth::user()->profile->avatar)
                            <img src="{{ asset('storage/' . Auth::user()->profile->avatar) }}"
                                 alt="Avatar"
                                 class="img-circle elevation-2"
                                 style="width: 34px; height: 34px; object-fit: cover;">
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

                        @if(Auth::user()->role === 'analista')
                            <li class="nav-item">
                                <a href="{{ route('videos.create') }}" class="nav-link {{ request()->routeIs('videos.create') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>Subir Video</p>
                                </a>
                            </li>
                        @endif

                        @if(Auth::user()->role !== 'jugador' && Auth::user()->role !== 'director_club')
                            <li class="nav-item">
                                <a href="{{ route('videos.index') }}" class="nav-link {{ request()->routeIs('videos.index') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>Videos del Equipo</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('my-videos') }}" class="nav-link {{ request()->routeIs('my-videos') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-circle"></i>
                                    <p>Mis Videos</p>
                                    @if(auth()->user()->pendingAssignments()->count() > 0)
                                        <span class="badge badge-warning navbar-badge">{{ auth()->user()->pendingAssignments()->count() }}</span>
                                    @endif
                                </a>
                            </li>
                        @endif

                        @if(Auth::user()->role === 'director_club')
                            <li class="nav-item">
                                <a href="{{ route('videos.index') }}" class="nav-link {{ request()->routeIs('videos.index') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>Videos del Equipo</p>
                                </a>
                            </li>
                        @endif

                        @if(Auth::user()->role === 'jugador')
                            <li class="nav-item">
                                <a href="{{ route('my-videos') }}" class="nav-link {{ request()->routeIs('my-videos') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>Mis Videos</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('videos.index') }}" class="nav-link {{ request()->routeIs('videos.index') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Videos del Equipo</p>
                                </a>
                            </li>
                            <!-- Funcionalidades Futuras -->
                            <li class="nav-item">
                                <a href="#" class="nav-link upcoming-feature" data-toggle="modal" data-target="#upcomingFeatureModal" data-feature="Jugadas">
                                    <i class="nav-icon fas fa-football-ball"></i>
                                    <p>
                                        Jugadas
                                        <span class="badge badge-success right">Próximamente</span>
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link upcoming-feature" data-toggle="modal" data-target="#upcomingFeatureModal" data-feature="Cuota Club">
                                    <i class="nav-icon fas fa-money-bill-wave"></i>
                                    <p>
                                        Cuota Club
                                        <span class="badge badge-success right">Próximamente</span>
                                    </p>
                                </a>
                            </li>
                        @endif

                        @if(Auth::user()->role === 'entrenador' || Auth::user()->role === 'analista')
                            <li class="nav-item">
                                <a href="{{ route('coach.users') }}" class="nav-link {{ request()->routeIs('coach.users') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Jugadores</p>
                                </a>
                            </li>
                        @endif

                        @if(Auth::user()->role === 'analista')
                            <li class="nav-header">ADMINISTRACIÓN</li>
                            <li class="nav-item">
                                <a href="{{ route('admin.index') }}" class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-tools"></i>
                                    <p>Mantenedor</p>
                                </a>
                            </li>
                            <!-- Funcionalidades Futuras -->
                            <li class="nav-item">
                                <a href="#" class="nav-link upcoming-feature" data-toggle="modal" data-target="#upcomingFeatureModal" data-feature="Gestión de Pagos">
                                    <i class="nav-icon fas fa-credit-card"></i>
                                    <p>
                                        Gestión de Pagos
                                        <span class="badge badge-success right">Próximamente</span>
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link upcoming-feature" data-toggle="modal" data-target="#upcomingFeatureModal" data-feature="Crear Jugadas">
                                    <i class="nav-icon fas fa-draw-polygon"></i>
                                    <p>
                                        Crear Jugadas
                                        <span class="badge badge-success right">Próximamente</span>
                                    </p>
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
                            <h1 class="m-0">@yield('page_title', 'Dashboard')</h1>
                        </div>
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
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
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
            Sistema de Análisis de Video Rugby Los Troncos
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
    <div class="modal fade" id="upcomingFeatureModal" tabindex="-1" role="dialog" aria-labelledby="upcomingFeatureModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e4d2b 0%, #28a745 100%); color: white;">
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
                        <strong>Nota:</strong> Estamos trabajando para traerte las mejores herramientas de análisis rugby.
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
        $('#featureDescription').text(featureDescriptions[featureName] || 'Esta funcionalidad está en desarrollo y estará disponible próximamente.');
    });
    </script>
</body>
</html>
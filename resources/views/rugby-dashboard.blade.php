<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-section {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            margin: 2rem 0;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .navbar {
            background: rgba(255,255,255,0.95) !important;
            backdrop-filter: blur(10px);
        }
        .badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
        }
        .system-stats {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                🏉 {{ config('app.name') }}
            </a>
            <div class="navbar-nav ms-auto">
                <span class="badge bg-success">Sistema Activo</span>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="hero-section text-center text-white">
            <h1 class="display-4 fw-bold mb-4">🏉 Sistema de Análisis de Rugby</h1>
            <p class="lead mb-4">Sistema web completo para análisis de videos del equipo "Los Troncos"</p>
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="system-stats">
                        <h3>{{ $teams->count() }}</h3>
                        <p class="mb-0">Equipos Registrados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="system-stats">
                        <h3>{{ $categories->count() }}</h3>
                        <p class="mb-0">Categorías</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="system-stats">
                        <h3>{{ $users->count() }}</h3>
                        <p class="mb-0">Usuarios</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="system-stats">
                        <h3>✅</h3>
                        <p class="mb-0">Base de Datos</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">🏈 Equipos del Campeonato</h5>
                    </div>
                    <div class="card-body">
                        @foreach($teams as $team)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold">{{ $team->name }}</span>
                                @if($team->is_own_team)
                                    <span class="badge bg-success">Nuestro Equipo</span>
                                @else
                                    <span class="badge bg-secondary">Rival</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">📊 Categorías</h5>
                    </div>
                    <div class="card-body">
                        @foreach($categories as $category)
                            <div class="mb-3">
                                <h6 class="fw-bold">{{ $category->name }}</h6>
                                <p class="text-muted small mb-0">{{ $category->description }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">👥 Usuarios del Sistema</h5>
                    </div>
                    <div class="card-body">
                        @foreach($users as $user)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <div class="fw-bold">{{ $user->name }}</div>
                                    <small class="text-muted">{{ $user->email }}</small>
                                </div>
                                <span class="badge 
                                    @if($user->role === 'analista') bg-primary
                                    @elseif($user->role === 'entrenador') bg-warning
                                    @elseif($user->role === 'jugador') bg-success
                                    @else bg-secondary
                                    @endif">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">🚀 Funcionalidades Implementadas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-success">✅ Completado</h6>
                                <ul class="list-unstyled">
                                    <li>✅ Configuración Laravel 12</li>
                                    <li>✅ Integración AdminLTE 3</li>
                                    <li>✅ Base de datos con migraciones</li>
                                    <li>✅ Modelos con relaciones</li>
                                    <li>✅ Seeders con datos de prueba</li>
                                    <li>✅ Sistema de equipos y categorías</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-warning">🔄 Pendiente</h6>
                                <ul class="list-unstyled">
                                    <li>🔄 Sistema de autenticación con roles</li>
                                    <li>🔄 Formularios de registro dual</li>
                                    <li>🔄 Sistema de subida de videos</li>
                                    <li>🔄 Reproductor con comentarios temporales</li>
                                    <li>🔄 Dashboards por rol</li>
                                    <li>🔄 Sistema de asignaciones</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 mb-4">
            <div class="hero-section">
                <h4 class="text-white">🎯 MVP del Sistema de Análisis de Rugby</h4>
                <p class="text-white-50">Base sólida para el desarrollo completo del sistema</p>
                <p class="text-white-50">Timezone configurado: America/Santiago (Chile)</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
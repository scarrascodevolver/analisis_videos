@extends('layouts.app')

@section('page_title', 'Dashboard Entrenador')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Dashboard Entrenador</li>
@endsection

@section('main_content')
    <!-- Welcome Section -->
    <div class="row">
        <div class="col-12">
            <div class="card card-rugby">
                <div class="card-body">
                    <h4>🏉 Bienvenido, {{ $user->name }}</h4>
                    <p class="text-muted">Panel de control completo para supervisar el análisis del equipo Los Troncos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Row -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box info-box-rugby">
                <div class="inner">
                    <h3>{{ $totalVideos }}</h3>
                    <p>Total Videos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-video"></i>
                </div>
                <a href="{{ route('coach.videos') }}" class="small-box-footer">
                    Ver todos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalUsers }}</h3>
                    <p>Usuarios del Sistema</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="{{ route('coach.users') }}" class="small-box-footer">
                    Gestionar <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $pendingAssignments }}</h3>
                    <p>Asignaciones Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <a href="{{ route('coach.assignments') }}" class="small-box-footer">
                    Supervisar <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $teams->where('is_own_team', false)->count() }}</h3>
                    <p>Equipos Rivales</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <a href="{{ route('coach.rivals') }}" class="small-box-footer">
                    Analizar <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions and Recent Videos -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bolt"></i>
                        Acciones Rápidas
                    </h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('coach.reports.team') }}" class="btn btn-rugby btn-lg btn-block mb-3">
                        <i class="fas fa-chart-bar"></i> Reporte del Equipo
                    </a>
                    <a href="{{ route('coach.players.compare') }}" class="btn btn-outline-primary btn-lg btn-block mb-3">
                        <i class="fas fa-balance-scale"></i> Comparar Jugadores
                    </a>
                    <a href="{{ route('coach.training.plan') }}" class="btn btn-outline-success btn-lg btn-block mb-3">
                        <i class="fas fa-dumbbell"></i> Plan de Entrenamiento
                    </a>
                    <a href="{{ route('coach.roster') }}" class="btn btn-outline-info btn-lg btn-block">
                        <i class="fas fa-list-alt"></i> Gestionar Roster
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-video"></i>
                        Videos Recientes del Equipo
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($recentVideos as $video)
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded video-card">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $video->title }}</h6>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge badge-primary mr-2">{{ $video->category->name }}</span>
                                    <small class="text-muted">
                                        {{ $video->analyzedTeam->name }} 
                                        @if($video->rivalTeam)
                                            vs {{ $video->rivalTeam->name }}
                                        @endif
                                    </small>
                                </div>
                                <small class="text-muted">
                                    Subido por: {{ $video->uploader->name }} 
                                    <span class="badge badge-sm 
                                        @if($video->uploader->role === 'analista') badge-info 
                                        @else badge-secondary @endif">
                                        {{ ucfirst($video->uploader->role) }}
                                    </span>
                                </small>
                            </div>
                            <div class="text-right">
                                <div class="mb-2">
                                    <span class="badge 
                                        @if($video->status === 'completed') badge-success
                                        @elseif($video->status === 'processing') badge-warning
                                        @else badge-secondary @endif">
                                        {{ ucfirst($video->status) }}
                                    </span>
                                </div>
                                <div>
                                    <a href="{{ route('videos.show', $video) }}" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                    <a href="{{ route('videos.analytics', $video) }}" 
                                       class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-chart-line"></i> Análisis
                                    </a>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    {{ $video->created_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay videos disponibles aún.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Team Performance and Analytics -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie"></i>
                        Análisis por Categoría
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trophy"></i>
                        Rendimiento vs Rivales
                    </h3>
                </div>
                <div class="card-body">
                    @php
                        $rivalTeams = $teams->where('is_own_team', false)->take(5);
                    @endphp
                    @foreach($rivalTeams as $rival)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0">{{ $rival->name }}</h6>
                                <small class="text-muted">Último enfrentamiento</small>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-success">3 videos</span>
                                <div>
                                    <small class="text-muted">Última victoria</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Player Performance Summary -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i>
                        Resumen de Rendimiento de Jugadores
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Jugador</th>
                                    <th>Posición</th>
                                    <th>Videos Asignados</th>
                                    <th>Completados</th>
                                    <th>Progreso</th>
                                    <th>Última Actividad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $players = \App\Models\User::where('role', 'jugador')->with('profile')->get();
                                @endphp
                                @foreach($players as $player)
                                    @php
                                        $assignments = \App\Models\VideoAssignment::where('assigned_to', $player->id);
                                        $completed = $assignments->clone()->where('status', 'completed')->count();
                                        $total = $assignments->count();
                                        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $player->name }}</strong><br>
                                            <small class="text-muted">{{ $player->email }}</small>
                                        </td>
                                        <td>
                                            {{ $player->profile->position ?? 'No especificada' }}
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $total }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">{{ $completed }}</span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: {{ $progress }}%">
                                                    {{ $progress }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">hace 2 horas</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('coach.player.profile', $player) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-user"></i>
                                            </a>
                                            <a href="{{ route('coach.player.assign', $player) }}" 
                                               class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Category Chart
    const ctx = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($categories->pluck('name')),
            datasets: [{
                data: [12, 8, 15, 5], // Example data
                backgroundColor: [
                    '#28a745',
                    '#007bff', 
                    '#ffc107',
                    '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>
@endsection
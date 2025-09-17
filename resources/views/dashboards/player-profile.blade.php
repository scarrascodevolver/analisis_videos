@extends('layouts.app')

@section('page_title', 'Perfil de ' . $user->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('coach.users') }}">Jugadores</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('main_content')
    <div class="row">
        <!-- Información del Jugador -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header text-center">
                    <h3 class="card-title">
                        <i class="fas fa-user"></i>
                        Información del Jugador
                    </h3>
                </div>
                <div class="card-body text-center">
                    <!-- Avatar -->
                    <div class="player-avatar-large mb-3 mx-auto">
                        {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->name)[1] ?? '', 0, 1)) }}
                    </div>

                    <!-- Nombre -->
                    <h4 class="text-rugby mb-3">{{ $user->name }}</h4>

                    <!-- Información del perfil -->
                    <div class="profile-info">
                        @if($user->profile)
                            <div class="info-item mb-3">
                                <i class="fas fa-football-ball text-rugby"></i>
                                <strong>Posición Principal:</strong><br>
                                <span class="text-muted">{{ $user->profile->position ?? 'No especificada' }}</span>
                            </div>

                            @if($user->profile->secondary_position)
                                <div class="info-item mb-3">
                                    <i class="fas fa-exchange-alt text-rugby"></i>
                                    <strong>Posición Secundaria:</strong><br>
                                    <span class="text-muted">{{ $user->profile->secondary_position }}</span>
                                </div>
                            @endif

                            <div class="info-item mb-3">
                                <i class="fas fa-layer-group text-rugby"></i>
                                <strong>Categoría:</strong><br>
                                <span class="category-badge-large">{{ $user->profile->category->name ?? 'Sin categoría' }}</span>
                            </div>

                            @if($user->profile->player_number)
                                <div class="info-item mb-3">
                                    <i class="fas fa-hashtag text-rugby"></i>
                                    <strong>Número:</strong><br>
                                    <span class="text-muted">{{ $user->profile->player_number }}</span>
                                </div>
                            @endif

                            @if($user->profile->weight || $user->profile->height)
                                <div class="info-item mb-3">
                                    <i class="fas fa-ruler text-rugby"></i>
                                    <strong>Físico:</strong><br>
                                    <span class="text-muted">
                                        @if($user->profile->height){{ $user->profile->height }}cm @endif
                                        @if($user->profile->weight)• {{ $user->profile->weight }}kg@endif
                                    </span>
                                </div>
                            @endif

                            @if($user->profile->date_of_birth)
                                <div class="info-item mb-3">
                                    <i class="fas fa-calendar text-rugby"></i>
                                    <strong>Fecha de Nacimiento:</strong><br>
                                    <span class="text-muted">{{ \Carbon\Carbon::parse($user->profile->date_of_birth)->format('d/m/Y') }}</span>
                                    <small class="d-block text-muted">
                                        ({{ \Carbon\Carbon::parse($user->profile->date_of_birth)->age }} años)
                                    </small>
                                </div>
                            @endif
                        @else
                            <p class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                No hay información de perfil disponible
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Videos Asignados -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-video"></i>
                        Videos Asignados
                    </h3>
                </div>
                <div class="card-body">
                    <div id="player-videos-container">
                        <!-- Loading inicial -->
                        <div class="text-center py-4">
                            <div class="spinner-border text-rugby" role="status">
                                <span class="sr-only">Cargando videos...</span>
                            </div>
                            <p class="mt-2 text-muted">Cargando videos del jugador...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
<style>
.player-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: #1e4d2b;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 2.5rem;
}

.text-rugby {
    color: #1e4d2b !important;
}

.category-badge-large {
    background: #28a745;
    color: white;
    border-radius: 15px;
    padding: 8px 16px;
    font-size: 1rem;
    font-weight: 600;
    display: inline-block;
}

.info-item {
    padding: 12px;
    border-left: 3px solid #1e4d2b;
    background: #f8f9fa;
    border-radius: 5px;
    text-align: left;
}

.info-item i {
    margin-right: 8px;
}

.video-card {
    transition: transform 0.2s ease;
    border: 1px solid #e9ecef;
}

.video-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(30, 77, 43, 0.1);
}

.video-thumbnail {
    height: 120px;
    background: #1e4d2b;
    border-radius: 8px;
    position: relative;
    overflow: hidden;
}

.video-thumbnail::before {
    content: '▶';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 2rem;
    opacity: 0.8;
}

.spinner-border.text-rugby {
    color: #1e4d2b !important;
}

.btn-rugby {
    background-color: #1e4d2b;
    border-color: #1e4d2b;
    color: white;
}

.btn-rugby:hover {
    background-color: #164023;
    border-color: #164023;
    color: white;
}
</style>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Cargar videos del jugador
    loadPlayerVideos({{ $user->id }});

    function loadPlayerVideos(playerId) {
        $.ajax({
            url: `/api/players/${playerId}/videos`,
            method: 'GET',
            success: function(data) {
                displayVideos(data.videos, data.stats);
            },
            error: function() {
                $('#player-videos-container').html(`
                    <div class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h6>Error cargando videos</h6>
                        <p class="text-muted">No se pudieron cargar los videos del jugador</p>
                    </div>
                `);
            }
        });
    }

    function displayVideos(videos, stats) {
        if (videos.length === 0) {
            $('#player-videos-container').html(`
                <div class="text-center py-4">
                    <i class="fas fa-video-slash fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No hay videos asignados</h6>
                    <p class="text-muted">Este jugador no tiene videos asignados aún</p>
                </div>
            `);
            return;
        }

        let html = '';

        // Estadísticas
        html += `
            <div class="row mb-4 text-center">
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <h3 class="text-rugby mb-0">${stats.total}</h3>
                        <small class="text-muted">Total Videos</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <h3 class="text-info mb-0">${stats.pending}</h3>
                        <small class="text-muted">Videos Asignados</small>
                    </div>
                </div>
            </div>
        `;

        // Videos
        html += '<div class="row">';
        videos.forEach(video => {
            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card video-card h-100">
                        <div class="video-thumbnail"></div>
                        <div class="card-body p-3">
                            <h6 class="card-title mb-2">${video.title}</h6>
                            <p class="card-text text-muted small mb-2">
                                ${video.analyzed_team?.name || 'Equipo'}
                                ${video.rival_team ? 'vs ' + video.rival_team.name : ''}
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    📅 ${formatDate(video.match_date)}
                                </small>
                                <span class="small text-info">
                                    📋 Asignado
                                </span>
                            </div>
                        </div>
                        <div class="card-footer p-2">
                            <a href="/videos/${video.id}" class="btn btn-rugby btn-sm btn-block">
                                <i class="fas fa-play"></i> Ver Video
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        $('#player-videos-container').html(html);
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
});
</script>
@endsection
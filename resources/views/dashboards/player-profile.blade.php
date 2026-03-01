@extends('layouts.app')

@section('page_title', 'Perfil del Jugador')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('coach.users') }}">Jugadores</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('main_content')
    <div class="row">
        <!-- Información del Jugador -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header text-center">
                    <h3 class="card-title mb-0">
                        {{ $user->name }}
                    </h3>
                </div>
                <div class="card-body text-center">

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
                                <i class="fas fa-trophy text-rugby"></i>
                                <strong>Categoría:</strong><br>
                                <span class="text-muted">{{ $user->profile->category->name ?? 'Sin categoría' }}</span>
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
                                        @if($user->profile->height)
                                            {{ $user->profile->height }}cm
                                        @endif
                                        @if($user->profile->weight)
                                            • {{ $user->profile->weight }}kg
                                        @endif
                                    </span>
                                </div>
                            @endif

                            @if($user->profile->date_of_birth)
                                <div class="info-item mb-3">
                                    <i class="fas fa-calendar text-rugby"></i>
                                    <strong>Edad:</strong><br>
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($user->profile->date_of_birth)->age }} años
                                        ({{ \Carbon\Carbon::parse($user->profile->date_of_birth)->format('d/m/Y') }})
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
    background: linear-gradient(135deg, var(--color-primary, #005461), var(--color-accent, #FFC300));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 2.5rem;
    box-shadow: 0 4px 15px rgba(255, 195, 0, 0.4);
}

.info-item {
    text-align: left;
    padding: 10px 0;
    border-bottom: 1px solid var(--color-secondary, #018790);
}

.info-item:last-child {
    border-bottom: none;
}

.info-item i {
    margin-right: 10px;
    width: 20px;
}

.info-item strong {
    color: var(--color-text, #ffffff);
}

.info-item .text-muted {
    color: #aaaaaa !important;
}

.text-rugby {
    color: var(--color-accent, #FFC300) !important;
}

.spinner-border.text-rugby {
    color: var(--color-accent, #FFC300) !important;
}

.video-card {
    transition: all 0.3s ease;
    border: 2px solid var(--color-secondary, #018790);
    border-radius: 10px;
    background: var(--color-bg-card, #0f0f0f);
}

.video-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(255, 195, 0, 0.25);
    border-color: var(--color-accent, #FFC300);
}

.video-card a {
    color: var(--color-text, #ffffff) !important;
}

.video-card .card-title {
    color: var(--color-text, #ffffff);
}

.video-card .card-text.text-muted {
    color: #aaaaaa !important;
}

.video-thumbnail-container {
    height: 120px;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 10px;
    background: linear-gradient(135deg, var(--color-primary, #005461), var(--color-accent, #FFC300));
}

.video-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
}

.play-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(255, 195, 0, 0.8);
    color: white;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.video-card:hover .play-overlay {
    background: rgba(255, 195, 0, 0.95);
    transform: translate(-50%, -50%) scale(1.1);
}

.btn-rugby {
    background-color: var(--color-primary, #005461);
    border-color: var(--color-primary, #005461);
    color: white;
    border-radius: 6px;
}

.btn-rugby:hover {
    background-color: var(--color-primary-hover, #003d4a);
    border-color: var(--color-primary-hover, #003d4a);
    color: white;
}

.stats-card {
    border: 2px solid var(--color-secondary, #018790);
    border-radius: 10px;
    transition: all 0.3s ease;
    background: var(--color-bg-card, #0f0f0f);
}

.stats-card:hover {
    border-color: var(--color-accent, #FFC300);
    box-shadow: 0 2px 8px rgba(255, 195, 0, 0.2);
}

.profile-info .text-muted {
    color: #aaaaaa !important;
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
            url: '/api/players/' + playerId + '/videos',
            method: 'GET',
            success: function(data) {
                displayVideos(data.videos, data.stats);
            },
            error: function(xhr) {
                console.error('Error cargando videos:', xhr);
                $('#player-videos-container').html(
                    '<div class="alert alert-warning">' +
                    '<i class="fas fa-exclamation-triangle"></i> ' +
                    'Error al cargar los videos del jugador' +
                    '</div>'
                );
            }
        });
    }

    function displayVideos(videos, stats) {
        if (!videos || videos.length === 0) {
            $('#player-videos-container').html(
                '<div class="text-center py-4">' +
                '<i class="fas fa-video-slash fa-3x text-muted mb-3"></i>' +
                '<h5 class="text-muted">No hay videos asignados</h5>' +
                '<p class="text-muted">Este jugador aún no tiene videos asignados para analizar</p>' +
                '</div>'
            );
            return;
        }

        let html = '';

        // Videos
        html += '<div class="row">';
        videos.forEach(function(video) {
            const teamName = video.analyzed_team ? video.analyzed_team.name : 'Equipo';
            const rivalText = video.rival_team ? ('vs ' + video.rival_team.name) : '';

            html += '<div class="col-md-6 col-lg-4 mb-3">';
            html += '<div class="card video-card h-100">';
            html += '<a href="/videos/' + video.id + '" class="text-decoration-none text-dark">';
            html += '<div class="video-thumbnail-container position-relative">';
            html += '<video class="video-thumbnail" preload="metadata" muted>';
            html += '<source src="/videos/' + video.id + '/stream#t=1" type="video/mp4">';
            html += '</video>';
            html += '<div class="play-overlay">';
            html += '<i class="fas fa-play"></i>';
            html += '</div>';
            html += '</div>';
            html += '<div class="card-body p-3">';
            html += '<h6 class="card-title mb-2">' + video.title + '</h6>';
            html += '<p class="card-text text-muted small mb-2">';
            html += teamName + ' ' + rivalText;
            html += '</p>';
            html += '<div class="d-flex justify-content-between align-items-center">';
            html += '<small class="text-muted">';
            html += '📅 ' + formatDate(video.match_date);
            html += '</small>';
            html += '</div>';
            html += '</div>';
            html += '</a>';
            html += '</div>';
            html += '</div>';
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
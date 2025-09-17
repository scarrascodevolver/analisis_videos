@extends('layouts.app')

@section('page_title', 'Gestión de Jugadores')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Jugadores</li>
@endsection

@section('main_content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i>
                        Gestión de Jugadores
                    </h3>
                </div>
                <div class="card-body">

                    <!-- Buscador AJAX -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="player-search">
                                    <i class="fas fa-search"></i> Buscar Jugador
                                </label>
                                <input type="text"
                                       id="player-search"
                                       class="form-control form-control-lg"
                                       placeholder="Escribe el nombre del jugador..."
                                       autocomplete="off">
                                <small class="form-text text-muted">
                                    Busca por nombre, posición o categoría
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="search-stats" class="mt-4 pt-2">
                                <span class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Escribe para buscar jugadores y ver sus videos asignados
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div id="loading-state" class="text-center py-4" style="display: none;">
                        <div class="spinner-border text-rugby" role="status">
                            <span class="sr-only">Buscando...</span>
                        </div>
                        <p class="mt-2 text-muted">Buscando jugadores...</p>
                    </div>

                    <!-- Resultados de búsqueda -->
                    <div id="search-results" class="row" style="display: none;">
                        <!-- Los resultados se cargarán aquí via AJAX -->
                    </div>

                    <!-- Estado vacío inicial -->
                    <div id="empty-state" class="text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Busca un jugador para comenzar</h4>
                        <p class="text-muted">
                            Escribe en el campo de búsqueda para encontrar jugadores<br>
                            y ver sus videos asignados y progreso
                        </p>
                    </div>

                    <!-- Sin resultados -->
                    <div id="no-results" class="text-center py-5" style="display: none;">
                        <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No se encontraron jugadores</h4>
                        <p class="text-muted">
                            Intenta con otro nombre o verifica la ortografía
                        </p>
                    </div>

                    <!-- Videos del jugador seleccionado -->
                    <div id="player-videos" style="display: none;">
                        <div class="border-top pt-4 mt-4">
                            <h5 id="player-videos-title">
                                <i class="fas fa-video"></i>
                                Videos asignados
                            </h5>
                            <div id="videos-grid" class="row">
                                <!-- Los videos se cargarán aquí via AJAX -->
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
<style>
.player-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    background: #f8f9fa;
}

.player-card:hover {
    border-color: #1e4d2b;
    background: #f0f4f1;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(30, 77, 43, 0.1);
}

.player-card.selected {
    border-color: #1e4d2b;
    background: #e8f5e8;
    box-shadow: 0 2px 4px rgba(30, 77, 43, 0.2);
}

.player-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #1e4d2b;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
}

.video-count-badge {
    background: #1e4d2b;
    color: white;
    border-radius: 15px;
    padding: 4px 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.category-badge {
    background: #28a745;
    color: white;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 0.75rem;
    font-weight: 500;
}

.position-text {
    color: #6c757d;
    font-size: 0.9rem;
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

.video-card {
    transition: transform 0.2s ease;
}

.video-card:hover {
    transform: translateY(-3px);
}

.spinner-border.text-rugby {
    color: #1e4d2b !important;
}

.form-control:focus {
    border-color: #1e4d2b;
    box-shadow: 0 0 0 0.2rem rgba(30, 77, 43, 0.25);
}
</style>
@endsection

@section('js')
<script>
$(document).ready(function() {
    let searchTimeout;
    let currentPlayerId = null;

    // Elementos del DOM
    const $searchInput = $('#player-search');
    const $loadingState = $('#loading-state');
    const $searchResults = $('#search-results');
    const $emptyState = $('#empty-state');
    const $noResults = $('#no-results');
    const $playerVideos = $('#player-videos');
    const $searchStats = $('#search-stats');

    // Búsqueda con debounce
    $searchInput.on('input', function() {
        const query = $(this).val().trim();

        clearTimeout(searchTimeout);

        if (query.length === 0) {
            showEmptyState();
            return;
        }

        if (query.length < 2) {
            return;
        }

        searchTimeout = setTimeout(() => {
            searchPlayers(query);
        }, 300);
    });

    function showEmptyState() {
        hideAllStates();
        $emptyState.show();
        $searchStats.html('<span class="text-muted"><i class="fas fa-info-circle"></i> Escribe para buscar jugadores y ver sus videos asignados</span>');
    }

    function showLoading() {
        hideAllStates();
        $loadingState.show();
    }

    function showNoResults() {
        hideAllStates();
        $noResults.show();
        $searchStats.html('<span class="text-muted"><i class="fas fa-exclamation-circle"></i> Sin resultados</span>');
    }

    function hideAllStates() {
        $loadingState.hide();
        $searchResults.hide();
        $emptyState.hide();
        $noResults.hide();
        $playerVideos.hide();
    }

    function searchPlayers(query) {
        showLoading();

        $.ajax({
            url: '/api/players/search',
            method: 'GET',
            data: { q: query },
            success: function(data) {
                if (data.players.length === 0) {
                    showNoResults();
                    return;
                }

                displayPlayers(data.players);

                const count = data.players.length;
                const plural = count !== 1 ? 's' : '';
                $searchStats.html(`<span class="text-success"><i class="fas fa-check-circle"></i> ${count} jugador${plural} encontrado${plural}</span>`);
            },
            error: function() {
                $searchStats.html('<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Error en la búsqueda</span>');
                showNoResults();
            }
        });
    }

    function displayPlayers(players) {
        hideAllStates();

        let html = '';
        players.forEach(player => {
            const initials = player.name.split(' ').map(n => n[0]).join('').toUpperCase();
            const position = player.profile?.position || 'Sin posición';
            const category = player.profile?.category?.name || 'Sin categoría';
            const videoCount = player.video_count || 0;

            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="player-card p-3" data-player-id="${player.id}">
                        <div class="d-flex align-items-center">
                            <div class="player-avatar me-3">
                                ${initials}
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 font-weight-bold">${player.name}</h6>
                                <p class="mb-1 position-text">${position}</p>
                                <span class="category-badge">${category}</span>
                            </div>
                            <div class="text-center">
                                <div class="video-count-badge">
                                    📺 ${videoCount}
                                </div>
                                <small class="text-muted d-block mt-1">videos</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $searchResults.html(html).show();

        // Agregar event listeners a las cards
        $('.player-card').on('click', function() {
            const playerId = $(this).data('player-id');
            const playerName = $(this).find('h6').text();

            $('.player-card').removeClass('selected');
            $(this).addClass('selected');

            loadPlayerVideos(playerId, playerName);
        });
    }

    function loadPlayerVideos(playerId, playerName) {
        currentPlayerId = playerId;

        $('#player-videos-title').html(`<i class="fas fa-video"></i> Videos asignados a ${playerName}`);
        $('#videos-grid').html('<div class="col-12 text-center py-4"><div class="spinner-border text-rugby" role="status"><span class="sr-only">Cargando videos...</span></div></div>');
        $playerVideos.show();

        $.ajax({
            url: `/api/players/${playerId}/videos`,
            method: 'GET',
            success: function(data) {
                displayPlayerVideos(data.videos, data.stats);
            },
            error: function() {
                $('#videos-grid').html('<div class="col-12 text-center py-4 text-danger"><i class="fas fa-exclamation-triangle"></i> Error cargando videos</div>');
            }
        });
    }

    function displayPlayerVideos(videos, stats) {
        if (videos.length === 0) {
            $('#videos-grid').html(`
                <div class="col-12 text-center py-4">
                    <i class="fas fa-video-slash fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No hay videos asignados</h6>
                    <p class="text-muted">Este jugador no tiene videos asignados aún</p>
                </div>
            `);
            return;
        }

        let html = '';

        videos.forEach(video => {
            const statusIcon = '📋'; // Generic assignment icon
            const statusText = 'Asignado';
            const statusClass = 'text-info';

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
                                <span class="small ${statusClass}">
                                    ${statusIcon} ${statusText}
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

        // Agregar estadísticas
        html = `
            <div class="col-12 mb-3">
                <div class="row text-center">
                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <h4 class="text-rugby mb-0">${stats.total}</h4>
                            <small class="text-muted">Total Videos</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <h4 class="text-info mb-0">${stats.pending}</h4>
                            <small class="text-muted">Videos Asignados</small>
                        </div>
                    </div>
                </div>
            </div>
        ` + html;

        $('#videos-grid').html(html);
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    // Inicializar vista
    showEmptyState();
});
</script>

<style>
.text-rugby {
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
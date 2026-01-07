@extends('layouts.app')

@section('page_title', 'Gestión de Jugadores y Entrenadores')

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
                        Gestión de Jugadores y Entrenadores
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

                    <!-- Todos los jugadores -->
                    <div id="all-players-section">
                        <div class="row mb-3">
                            <div class="col-12">
                                <h5 class="text-rugby">
                                    <i class="fas fa-users"></i>
                                    Todos los Jugadores y Entrenadores
                                </h5>
                                <hr class="mb-4">
                            </div>
                        </div>
                        <div id="all-players-grid" class="row">
                            <!-- Los jugadores se cargarán aquí al iniciar -->
                        </div>
                    </div>

                    <!-- Estado vacío inicial (se oculta cuando se cargan jugadores) -->
                    <div id="empty-state" class="text-center py-5" style="display: none;">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Busca un jugador o entrenador para comenzar</h4>
                        <p class="text-muted">
                            Escribe en el campo de búsqueda para encontrar jugadores y entrenadores<br>
                            y ver sus videos asignados y progreso
                        </p>
                    </div>

                    <!-- Sin resultados -->
                    <div id="no-results" class="text-center py-5" style="display: none;">
                        <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No se encontraron jugadores ni entrenadores</h4>
                        <p class="text-muted">
                            Intenta con otro nombre o verifica la ortografía
                        </p>
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
    border-radius: 15px;
    background: #fff;
    min-height: 220px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.player-card:hover {
    border-color: var(--color-primary, #005461);
    background: #f8fffe;
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 84, 97, 0.15);
}

.player-card.selected {
    border-color: var(--color-primary, #005461);
    background: #e8f5e8;
    box-shadow: 0 4px 15px rgba(0, 84, 97, 0.2);
}

/* Avatar centrado más grande */
.player-avatar-center {
    width: 85px;
    height: 85px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-primary, #005461), var(--color-accent, #4B9DA9));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.8rem;
    box-shadow: 0 4px 10px rgba(0, 84, 97, 0.3);
}

/* Nombre del jugador */
.player-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 15px !important;
    line-height: 1.3;
}

/* Sección de posiciones */
.positions-info {
    min-height: 60px;
}

.position-primary {
    margin-bottom: 8px;
}

.position-secondary {
    margin-bottom: 8px;
}

.position-text {
    color: var(--color-primary, #005461);
    font-size: 0.95rem;
    font-weight: 500;
    margin-left: 5px;
}

.position-text-secondary {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 400;
    margin-left: 5px;
}

/* Badges nuevos */
.category-badge-new {
    background: linear-gradient(135deg, var(--color-accent, #4B9DA9), #20c997);
    color: white;
    border-radius: 20px;
    padding: 8px 16px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
    box-shadow: 0 2px 8px rgba(0, 183, 181, 0.3);
}

.video-count-badge-new {
    background: linear-gradient(135deg, var(--color-primary, #005461), var(--color-primary-hover, #003d4a));
    color: white;
    border-radius: 20px;
    padding: 10px 16px;
    font-size: 1rem;
    font-weight: 600;
    display: inline-block;
    min-width: 120px;
    box-shadow: 0 3px 10px rgba(0, 84, 97, 0.3);
}

/* Iconos rugby */
.fas.fa-football-ball,
.fas.fa-exchange-alt {
    font-size: 0.9rem;
    margin-right: 5px;
}

.video-thumbnail {
    height: 120px;
    background: var(--color-primary, #005461);
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
    color: var(--color-primary, #005461) !important;
}

.form-control:focus {
    border-color: var(--color-primary, #005461);
    box-shadow: 0 0 0 0.2rem rgba(0, 84, 97, 0.25);
}
</style>
@endsection

@section('js')
<script>
$(document).ready(function() {
    let searchTimeout;

    // Elementos del DOM
    const $searchInput = $('#player-search');
    const $loadingState = $('#loading-state');
    const $searchResults = $('#search-results');
    const $emptyState = $('#empty-state');
    const $noResults = $('#no-results');
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
        $('#all-players-section').show();
        loadAllPlayers();
        $searchStats.html('<span class="text-muted"><i class="fas fa-info-circle"></i> Escribe para buscar jugadores específicos</span>');
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
        $('#all-players-section').hide();
    }

    function loadAllPlayers() {
        $.ajax({
            url: '/api/players/all',
            method: 'GET',
            success: function(data) {
                if (data.players.length === 0) {
                    $('#all-players-grid').html(`
                        <div class="col-12 text-center py-4">
                            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No hay jugadores registrados</h6>
                        </div>
                    `);
                    return;
                }

                renderPlayersGrid(data.players, '#all-players-grid');
            },
            error: function() {
                $('#all-players-grid').html(`
                    <div class="col-12 text-center py-4 text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error cargando jugadores
                    </div>
                `);
            }
        });
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

    function renderPlayersGrid(players, targetContainer) {
        let html = '';
        players.forEach(player => {
            const initials = player.name.split(' ').map(n => n[0]).join('').toUpperCase();
            const position = player.profile?.position || 'Sin posición';
            const secondaryPosition = player.profile?.secondary_position;
            const category = player.profile?.category?.name || 'Sin categoría';
            const videoCount = player.video_count || 0;
            const avatar = player.profile?.avatar;

            html += `
                <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
                    <div class="player-card text-center p-4" data-player-id="${player.id}">
                        <!-- Avatar centrado -->
                        <div class="player-avatar-center mx-auto mb-3">
                            ${avatar ?
                                `<img src="/storage/${avatar}" alt="${player.name}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">` :
                                initials
                            }
                        </div>

                        <!-- Nombre del jugador -->
                        <h6 class="player-name mb-3">${player.name}</h6>

                        <!-- Posiciones -->
                        <div class="positions-info mb-3">
                            <div class="position-primary mb-2">
                                <i class="fas fa-football-ball text-rugby"></i>
                                <span class="position-text">${position}</span>
                            </div>
                            ${secondaryPosition ? `
                                <div class="position-secondary mb-2">
                                    <i class="fas fa-exchange-alt text-rugby"></i>
                                    <span class="position-text-secondary">${secondaryPosition}</span>
                                </div>
                            ` : ''}
                        </div>

                        <!-- Categoría -->
                        <div class="category-section mb-3">
                            <span class="category-badge-new">${category}</span>
                        </div>

                        <!-- Contador de videos -->
                        <div class="video-count-section">
                            <div class="video-count-badge-new">
                                📺 ${videoCount}
                            </div>
                            <small class="text-muted d-block">videos asignados</small>
                        </div>
                    </div>
                </div>
            `;
        });

        $(targetContainer).html(html);

        // Agregar event listeners a las cards - CAMBIO: navegar en lugar de mostrar videos
        $('.player-card').off('click').on('click', function() {
            const playerId = $(this).data('player-id');

            // Navegar al perfil del jugador
            window.location.href = `/coach/player/${playerId}`;
        });
    }

    function displayPlayers(players) {
        hideAllStates();
        renderPlayersGrid(players, '#search-results');
        $searchResults.show();
    }

    // Inicializar vista
    showEmptyState();
});
</script>

<style>
.text-rugby {
    color: var(--color-primary, #005461) !important;
}

.btn-rugby {
    background-color: var(--color-primary, #005461);
    border-color: var(--color-primary, #005461);
    color: white;
}

.btn-rugby:hover {
    background-color: var(--color-primary-hover, #003d4a);
    border-color: var(--color-primary-hover, #003d4a);
    color: white;
}
</style>
@endsection
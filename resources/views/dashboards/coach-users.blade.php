@extends('layouts.app')

@section('page_title', 'Plantilla')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Plantilla</li>
@endsection

@section('main_content')
<div class="container-fluid">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0" style="color:#00B7B5;">
                <i class="fas fa-users mr-2"></i>Plantilla
            </h4>
            <small class="text-muted" id="player-counter">Cargando...</small>
        </div>
        <div style="width:260px;">
            <div class="input-group input-group-sm">
                <div class="input-group-prepend">
                    <span class="input-group-text" style="background:#005461; border-color:#005461;">
                        <i class="fas fa-search text-white"></i>
                    </span>
                </div>
                <input type="text" id="player-search" class="form-control"
                       placeholder="Buscar jugador..." autocomplete="off">
            </div>
        </div>
    </div>

    <!-- Tabs de categoría -->
    <ul class="nav nav-tabs mb-4" id="category-tabs" style="border-bottom:2px solid #005461;">
        <li class="nav-item">
            <a class="nav-link active" href="#" data-category="all">Todas</a>
        </li>
        <!-- Tabs dinámicas por JS -->
    </ul>

    <!-- Loading -->
    <div id="loading-state" class="text-center py-5">
        <div class="spinner-border" role="status" style="color:#00B7B5;"></div>
        <p class="mt-2 text-muted">Cargando plantilla...</p>
    </div>

    <!-- Grid de jugadores -->
    <div id="players-container" style="display:none;"></div>

    <!-- Sin resultados -->
    <div id="no-results" class="text-center py-5" style="display:none;">
        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No se encontraron jugadores</h5>
    </div>

</div>
@endsection

@section('css')
<style>
/* Tabs */
#category-tabs .nav-link {
    color: #aaa;
    border: none;
    border-bottom: 3px solid transparent;
    border-radius: 0;
    padding: 8px 18px;
    font-weight: 500;
    transition: all .2s;
}
#category-tabs .nav-link:hover { color: #00B7B5; }
#category-tabs .nav-link.active {
    color: #00B7B5 !important;
    border-bottom-color: #00B7B5 !important;
    background: transparent;
    font-weight: 700;
}

/* Sección de unidad */
.unit-section { margin-bottom: 2rem; }
.unit-title {
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #00B7B5;
    border-bottom: 1px solid #1a3a3a;
    padding-bottom: 6px;
    margin-bottom: 1rem;
}

/* Cards */
.player-card {
    cursor: pointer;
    border: 1.5px solid #1a3a3a;
    border-radius: 12px;
    background: #141414;
    transition: all .25s;
    padding: 20px 14px;
    text-align: center;
}
.player-card:hover {
    border-color: #00B7B5;
    background: #0d2626;
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(0,183,181,.2);
}
.player-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #005461, #00B7B5);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 1.5rem;
    margin: 0 auto 10px;
    overflow: hidden;
}
.player-avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
.player-name { font-weight: 600; font-size: .95rem; color: #fff; margin-bottom: 6px; }
.player-position { font-size: .82rem; color: #00B7B5; margin-bottom: 3px; }
.player-position-2 { font-size: .78rem; color: #888; margin-bottom: 8px; }
.player-category-badge {
    display: inline-block;
    background: #005461;
    color: #fff;
    border-radius: 20px;
    padding: 2px 12px;
    font-size: .75rem;
    font-weight: 600;
    margin-bottom: 8px;
}
.player-videos {
    font-size: .8rem;
    color: #888;
}
.player-videos span { color: #00B7B5; font-weight: 700; }

/* Search highlight */
#player-search:focus {
    border-color: #00B7B5;
    box-shadow: 0 0 0 .15rem rgba(0,183,181,.25);
}
</style>
@endsection

@section('js')
<script>
// Posiciones rugby → unidad táctica
const FORWARDS = ['pilar','hooker','talonador','lock','segunda línea','flanker','ala','número 8','n°8','n8','no8','forward','pilar izquierdo','pilar derecho'];
const BACKS    = ['medio scrum','medio de scrum','apertura','centro','wing','ala tres cuartos','fullback','full','tres cuartos','backs','back','volante'];

function unitOf(position) {
    if (!position) return 'sin clasificar';
    const p = position.toLowerCase();
    if (FORWARDS.some(f => p.includes(f))) return 'forwards';
    if (BACKS.some(b => p.includes(b))) return 'backs';
    return 'sin clasificar';
}

let allPlayers = [];
let activeCategory = 'all';
let searchQuery = '';

// Cargar todos los jugadores al iniciar
fetch('/api/players/all')
    .then(r => r.json())
    .then(data => {
        allPlayers = data.players || [];
        buildCategoryTabs();
        render();
        document.getElementById('loading-state').style.display = 'none';
        document.getElementById('players-container').style.display = 'block';
    });

function buildCategoryTabs() {
    const categories = {};
    allPlayers.forEach(p => {
        const cat = p.profile?.category;
        if (cat && !categories[cat.id]) categories[cat.id] = cat.name;
    });

    const tabs = document.getElementById('category-tabs');
    Object.entries(categories).forEach(([id, name]) => {
        const li = document.createElement('li');
        li.className = 'nav-item';
        li.innerHTML = `<a class="nav-link" href="#" data-category="${id}">${name}</a>`;
        tabs.appendChild(li);
    });

    tabs.addEventListener('click', function(e) {
        const link = e.target.closest('[data-category]');
        if (!link) return;
        e.preventDefault();
        tabs.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        activeCategory = link.dataset.category;
        render();
    });
}

function filteredPlayers() {
    return allPlayers.filter(p => {
        const matchCat = activeCategory === 'all'
            || String(p.profile?.category?.id) === String(activeCategory);
        const q = searchQuery.toLowerCase();
        const matchSearch = !q
            || p.name.toLowerCase().includes(q)
            || (p.profile?.position || '').toLowerCase().includes(q)
            || (p.profile?.category?.name || '').toLowerCase().includes(q);
        return matchCat && matchSearch;
    });
}

function render() {
    const players = filteredPlayers();
    const container = document.getElementById('players-container');
    const noResults = document.getElementById('no-results');

    // Contador
    const total = players.filter(p => p.profile?.position).length; // solo jugadores con posición
    document.getElementById('player-counter').textContent =
        `${players.length} jugador${players.length !== 1 ? 'es' : ''}`;

    if (players.length === 0) {
        container.innerHTML = '';
        noResults.style.display = 'block';
        return;
    }
    noResults.style.display = 'none';

    // Agrupar por unidad
    const groups = { forwards: [], backs: [], 'sin clasificar': [] };
    players.forEach(p => {
        const unit = unitOf(p.profile?.position);
        groups[unit].push(p);
    });

    const unitLabels = {
        forwards: '<i class="fas fa-fist-raised mr-1"></i> Forwards',
        backs: '<i class="fas fa-running mr-1"></i> Backs',
        'sin clasificar': '<i class="fas fa-user mr-1"></i> Sin clasificar',
    };

    let html = '';
    ['forwards', 'backs', 'sin clasificar'].forEach(unit => {
        if (!groups[unit].length) return;
        html += `<div class="unit-section">
            <div class="unit-title">${unitLabels[unit]} <span class="font-weight-normal">(${groups[unit].length})</span></div>
            <div class="row">`;
        groups[unit].forEach(p => {
            html += playerCard(p);
        });
        html += `</div></div>`;
    });

    container.innerHTML = html;
}

function playerCard(p) {
    const initials = p.name.split(' ').map(n => n[0]).join('').slice(0,2).toUpperCase();
    const avatarInner = p.profile?.avatar
        ? `<img src="/storage/${p.profile.avatar}" alt="${p.name}">`
        : initials;
    const pos2 = p.profile?.secondary_position
        ? `<div class="player-position-2"><i class="fas fa-exchange-alt mr-1"></i>${p.profile.secondary_position}</div>` : '';
    const cat = p.profile?.category?.name || '';
    const videos = p.video_count || 0;

    return `
        <div class="col-6 col-md-4 col-lg-3 col-xl-2 mb-3">
            <div class="player-card" onclick="window.location='/coach/player/${p.id}'">
                <div class="player-avatar">${avatarInner}</div>
                <div class="player-name">${p.name}</div>
                <div class="player-position">${p.profile?.position || 'Sin posición'}</div>
                ${pos2}
                ${cat ? `<div class="player-category-badge">${cat}</div>` : ''}
                <div class="player-videos">📺 <span>${videos}</span> videos</div>
            </div>
        </div>`;
}

// Búsqueda
let searchTimeout;
document.getElementById('player-search').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchQuery = this.value.trim();
        render();
    }, 250);
});
</script>
@endsection

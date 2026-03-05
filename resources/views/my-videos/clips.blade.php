@extends('layouts.app')

@section('page_title', 'Clips Recibidos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('my-videos') }}">Mis Videos</a></li>
    <li class="breadcrumb-item active">Clips Recibidos</li>
@endsection

@section('main_content')

    <!-- Tabs de navegación -->
    <ul class="nav nav-tabs mb-3" style="border-bottom:1px solid #2c2c2c;">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('my-videos') }}" style="color:#aaa;">
                <i class="fas fa-video mr-1"></i> Videos Asignados
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('my-videos.clips') }}" style="color:#00B7B5;border-color:#2c2c2c #2c2c2c transparent;">
                <i class="fas fa-film mr-1"></i> Clips Recibidos
                @if($unreadCount > 0)
                    <span class="badge badge-pill" style="background:#00B7B5;color:#fff;font-size:10px;">{{ $unreadCount }}</span>
                @endif
            </a>
        </li>
    </ul>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-film"></i>
                Clips compartidos conmigo
            </h3>
        </div>
        <div class="card-body">
            @if($sharedClips->count() > 0)
                <div class="shared-clips-list">
                    @foreach($sharedClips as $share)
                        <div class="shared-clip-card {{ is_null($share->read_at) ? 'unread' : '' }}" data-id="{{ $share->id }}">
                            <!-- Categoria + tiempo -->
                            <div class="sc-category" style="background: {{ $share->clip?->category?->color ?? '#005461' }}20; border-left: 3px solid {{ $share->clip?->category?->color ?? '#00B7B5' }};">
                                <span class="sc-category-name">
                                    {{ $share->clip?->category?->name ?? 'Sin categoría' }}
                                </span>
                                <span class="sc-time">
                                    @php
                                        $start = (int)($share->clip?->start_time ?? 0);
                                        $end   = (int)($share->clip?->end_time ?? 0);
                                    @endphp
                                    {{ sprintf('%02d:%02d', floor($start/60), $start%60) }}
                                    –
                                    {{ sprintf('%02d:%02d', floor($end/60), $end%60) }}
                                </span>
                                @if(is_null($share->read_at))
                                    <span class="sc-new-badge">Nuevo</span>
                                @endif
                            </div>

                            <!-- Cuerpo -->
                            <div class="sc-body">
                                <div class="sc-meta">
                                    <span class="sc-video-title">
                                        <i class="fas fa-video"></i>
                                        {{ $share->video?->title ?? 'Video eliminado' }}
                                    </span>
                                    <span class="sc-from">
                                        <i class="fas fa-user"></i>
                                        {{ $share->sharedBy?->name ?? '—' }}
                                        <span class="sc-org">{{ $share->fromOrganization?->name ?? '' }}</span>
                                    </span>
                                    <span class="sc-date text-muted">
                                        <i class="fas fa-clock"></i>
                                        {{ $share->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                @if($share->message)
                                    <div class="sc-message">
                                        <i class="fas fa-comment-alt"></i>
                                        {{ $share->message }}
                                    </div>
                                @endif

                                <!-- Acciones -->
                                <div class="sc-actions">
                                    @if($share->clip?->share_token)
                                        <a
                                            href="{{ route('clips.public', $share->clip->share_token) }}"
                                            target="_blank"
                                            class="btn btn-rugby btn-sm sc-play-btn"
                                            data-share-id="{{ $share->id }}"
                                        >
                                            <i class="fas fa-play"></i> Ver clip
                                        </a>
                                    @else
                                        <a
                                            href="{{ route('clips.share', $share->clip?->id ?? 0) }}"
                                            target="_blank"
                                            class="btn btn-rugby btn-sm sc-play-btn"
                                            data-share-id="{{ $share->id }}"
                                        >
                                            <i class="fas fa-play"></i> Ver clip
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $sharedClips->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-film fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No tienes clips compartidos</h4>
                    <p class="text-muted">Cuando un analista o entrenador comparta un clip contigo, aparecerá aquí.</p>
                </div>
            @endif
        </div>
    </div>

@endsection

@section('css')
<style>
    .shared-clips-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .shared-clip-card {
        background: #1a1a1a;
        border: 1px solid #2c2c2c;
        border-radius: 6px;
        overflow: hidden;
        transition: border-color 0.2s;
    }
    .shared-clip-card:hover { border-color: #005461; }
    .shared-clip-card.unread { border-color: rgba(0, 183, 181, 0.4); }

    .sc-category {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.45rem 0.85rem;
    }
    .sc-category-name {
        font-size: 11px;
        font-weight: 600;
        color: #ccc;
    }
    .sc-time {
        font-size: 11px;
        color: #00B7B5;
        font-family: monospace;
        margin-left: auto;
    }
    .sc-new-badge {
        background: #00B7B5;
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        padding: 0.1rem 0.4rem;
        border-radius: 3px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .sc-body {
        padding: 0.65rem 0.85rem;
    }

    .sc-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.85rem;
        font-size: 11.5px;
        margin-bottom: 0.5rem;
    }
    .sc-meta i { color: #00B7B5; margin-right: 0.25rem; font-size: 10px; }
    .sc-video-title { color: #ddd; font-weight: 500; }
    .sc-from { color: #aaa; }
    .sc-org {
        display: inline-block;
        background: rgba(0, 84, 97, 0.4);
        color: #00B7B5;
        font-size: 9.5px;
        padding: 0.1rem 0.35rem;
        border-radius: 3px;
        margin-left: 0.25rem;
    }
    .sc-date { font-size: 10.5px; }

    .sc-message {
        background: #252525;
        border-left: 2px solid #005461;
        border-radius: 3px;
        padding: 0.4rem 0.65rem;
        font-size: 12px;
        color: #bbb;
        margin-bottom: 0.5rem;
        font-style: italic;
    }
    .sc-message i { color: #005461; margin-right: 0.35rem; }

    .sc-actions { display: flex; gap: 0.5rem; }

    .sc-play-btn {
        font-size: 11px;
        padding: 0.3rem 0.75rem;
    }
</style>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // Marcar como leído al hacer click en "Ver clip"
    document.querySelectorAll('.sc-play-btn[data-share-id]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const shareId = btn.dataset.shareId;
            const card = btn.closest('.shared-clip-card');
            if (card && card.classList.contains('unread')) {
                fetch('/api/shared-clips/' + shareId + '/read', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                });
                card.classList.remove('unread');
                const badge = card.querySelector('.sc-new-badge');
                if (badge) badge.remove();
            }
        });
    });
});
</script>
@endsection

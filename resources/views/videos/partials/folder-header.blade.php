<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 text-muted">
        @isset($back)
            <a href="{{ $back }}" class="text-muted mr-2" title="Volver"><i class="fas fa-arrow-left"></i></a>
        @endisset
        <i class="fas fa-{{ $icon }} mr-2"></i>{{ $title }}
    </h5>
    <a href="{{ route('videos.create') }}" class="btn btn-rugby btn-sm">
        <i class="fas fa-plus mr-1"></i> Subir Video
    </a>
</div>

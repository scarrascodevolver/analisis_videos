<div class="card card-rugby">
    <div class="card-body text-center py-5">
        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
        <p class="text-muted mb-2">{{ $msg }}</p>
        @if(isset($action))
            <a href="{{ $action }}" class="btn btn-rugby btn-sm mt-1">
                <i class="fas fa-plus mr-1"></i>{{ $actionLabel ?? 'Subir Video' }}
            </a>
        @endif
    </div>
</div>

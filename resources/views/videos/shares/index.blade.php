@extends('layouts.app')

@section('page_title', 'Compartidos — ' . $video->title)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('videos.show', $video) }}">{{ Str::limit($video->title, 30) }}</a></li>
    <li class="breadcrumb-item active">Compartidos</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
<div class="col-lg-9">

    <div class="card card-rugby">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">
                <i class="fas fa-share-alt mr-2" style="color:#00B7B5;"></i>
                Videos enviados a clubes
            </h3>
            <a href="{{ route('videos.show', $video) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver al video
            </a>
        </div>
        <div class="card-body p-0">

            @if(session('success'))
                <div class="alert alert-success m-3">{{ session('success') }}</div>
            @endif

            @if($shares->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-share-alt fa-3x mb-3" style="color:#005461;opacity:.4;"></i>
                    <p>Este video no ha sido enviado a ningún club aún.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Club</th>
                                <th>Categoría</th>
                                <th>Enviado por</th>
                                <th>Fecha</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="sharesTableBody">
                            @foreach($shares as $share)
                            <tr id="share-row-{{ $share->id }}">
                                <td>
                                    <strong>{{ $share->targetOrganization->name ?? '—' }}</strong>
                                </td>
                                <td>
                                    @if($share->targetCategory)
                                        {{ $share->targetCategory->name }}
                                    @else
                                        <span class="text-muted">Sin categoría</span>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ $share->sharedByUser->name ?? '—' }}
                                </td>
                                <td class="text-muted small">
                                    {{ $share->shared_at?->format('d/m/Y H:i') }}
                                </td>
                                <td class="text-right">
                                    <button type="button"
                                            class="btn btn-xs btn-outline-danger btn-revoke-share"
                                            data-share-id="{{ $share->id }}"
                                            data-org-name="{{ $share->targetOrganization->name ?? '?' }}"
                                            title="Revocar acceso">
                                        <i class="fas fa-ban mr-1"></i> Revocar
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>

</div>
</div>
@endsection

@section('js')
<script>
document.querySelectorAll('.btn-revoke-share').forEach(btn => {
    btn.addEventListener('click', function () {
        const shareId = this.dataset.shareId;
        const orgName = this.dataset.orgName;

        if (!confirm(`¿Revocar el acceso de "${orgName}" a este video?`)) return;

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch(`/shares/${shareId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                const row = document.getElementById('share-row-' + shareId);
                row?.remove();

                const tbody = document.getElementById('sharesTableBody');
                if (tbody && tbody.children.length === 0) {
                    location.reload();
                }
            } else {
                alert(data.error || 'Error al revocar.');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-ban mr-1"></i> Revocar';
            }
        })
        .catch(() => {
            alert('Error de red.');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-ban mr-1"></i> Revocar';
        });
    });
});
</script>
@endsection

{{-- Badge de video compartido por una asociación --}}
{{-- Uso: @include('videos.partials.shared-badge', ['share' => $share]) --}}
<span class="badge badge-shared-org"
      style="background: #00B7B5; color: #fff; font-size: 0.7rem; padding: 3px 7px; border-radius: 4px; white-space: nowrap;"
      title="Enviado por {{ $share->sourceOrganization->name ?? 'una asociación' }} el {{ $share->shared_at?->format('d/m/Y') }}">
    <i class="fas fa-share-alt mr-1" style="font-size:0.65rem;"></i>
    {{ $share->sourceOrganization->name ?? 'Asociación' }}
</span>

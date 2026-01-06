@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-building mr-2"></i>
                        Selecciona una Organizacion
                    </h4>
                </div>

                <div class="card-body">
                    @if(session('info'))
                        <div class="alert alert-info">
                            {{ session('info') }}
                        </div>
                    @endif

                    <p class="text-muted mb-4">
                        Tu cuenta tiene acceso a multiples organizaciones. Selecciona con cual deseas trabajar:
                    </p>

                    <div class="row">
                        @foreach($organizations as $org)
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 {{ $currentOrg && $currentOrg->id === $org->id ? 'border-success' : '' }}">
                                    <div class="card-body text-center">
                                        @if($org->logo_path)
                                            <img src="{{ asset('storage/' . $org->logo_path) }}"
                                                 alt="{{ $org->name }}"
                                                 class="img-fluid mb-3"
                                                 style="max-height: 80px;">
                                        @else
                                            <div class="mb-3">
                                                <i class="fas fa-shield-alt fa-4x text-success"></i>
                                            </div>
                                        @endif

                                        <h5 class="card-title">{{ $org->name }}</h5>

                                        <p class="text-muted small mb-3">
                                            <i class="fas fa-user-tag mr-1"></i>
                                            Rol: <strong>{{ ucfirst($org->pivot->role) }}</strong>
                                        </p>

                                        @if($currentOrg && $currentOrg->id === $org->id)
                                            <span class="badge badge-success mb-2">
                                                <i class="fas fa-check mr-1"></i> Actual
                                            </span>
                                        @endif

                                        <form action="{{ route('set-organization', $org) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="btn {{ $currentOrg && $currentOrg->id === $org->id ? 'btn-outline-success' : 'btn-success' }} btn-block">
                                                @if($currentOrg && $currentOrg->id === $org->id)
                                                    <i class="fas fa-check mr-1"></i> Continuar
                                                @else
                                                    <i class="fas fa-sign-in-alt mr-1"></i> Seleccionar
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-footer text-center text-muted">
                    <small>
                        <i class="fas fa-info-circle mr-1"></i>
                        Puedes cambiar de organizacion en cualquier momento desde el menu superior
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('page_title', 'Detalle de Usuario')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-lg-8">
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">Información del Usuario</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 text-center">
                        @if($user->profile && $user->profile->avatar)
                            <img src="{{ asset('storage/' . $user->profile->avatar) }}"
                                 alt="Avatar"
                                 class="img-circle elevation-2"
                                 style="width: 120px; height: 120px; object-fit: cover;">
                        @else
                            <i class="fas fa-user-circle fa-7x text-muted"></i>
                        @endif
                    </div>
                    <div class="col-md-9">
                        <h4>{{ $user->name }}</h4>
                        <p class="text-muted">{{ $user->email }}</p>

                        <div class="mb-2">
                            <strong>Rol:</strong>
                            @php
                                $roleColors = [
                                    'jugador' => 'primary',
                                    'entrenador' => 'success',
                                    'analista' => 'warning',
                                    'staff' => 'info',
                                    'director_club' => 'danger',
                                    'director_tecnico' => 'secondary'
                                ];
                                $color = $roleColors[$user->role] ?? 'dark';
                            @endphp
                            <span class="badge badge-{{ $color }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </div>

                        @if($user->profile && $user->profile->category)
                            <div class="mb-2">
                                <strong>Categoría:</strong>
                                <span class="badge badge-info">{{ $user->profile->category->name }}</span>
                            </div>
                        @endif

                        <div class="mb-2">
                            <strong>Miembro desde:</strong> {{ $user->created_at->format('d/m/Y') }}
                        </div>
                    </div>
                </div>

                <hr>

                <h5>Perfil Adicional</h5>
                @if($user->profile)
                    <div class="row">
                        @if($user->profile->position)
                            <div class="col-md-6">
                                <strong>Posición:</strong> {{ $user->profile->position }}
                            </div>
                        @endif

                        @if($user->profile->player_number)
                            <div class="col-md-6">
                                <strong>Número:</strong> #{{ $user->profile->player_number }}
                            </div>
                        @endif

                        @if($user->profile->weight)
                            <div class="col-md-6">
                                <strong>Peso:</strong> {{ $user->profile->weight }} kg
                            </div>
                        @endif

                        @if($user->profile->height)
                            <div class="col-md-6">
                                <strong>Altura:</strong> {{ $user->profile->height }} cm
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-muted">No hay información de perfil adicional.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Actividad</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Videos Subidos:</strong>
                    <span class="badge badge-primary">{{ $user->videos->count() }}</span>
                </div>

                @if($user->role === 'jugador')
                    <div class="mb-3">
                        <strong>Videos Asignados:</strong>
                        <span class="badge badge-warning">{{ $user->assignments->count() }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

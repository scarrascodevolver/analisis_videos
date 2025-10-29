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
        <!-- Información del Usuario -->
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i>Información del Usuario</h3>
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
                        <p class="text-muted"><i class="fas fa-envelope mr-1"></i>{{ $user->email }}</p>

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
                                $roleIcons = [
                                    'jugador' => 'fa-futbol',
                                    'entrenador' => 'fa-chalkboard-teacher',
                                    'analista' => 'fa-chart-line',
                                    'staff' => 'fa-users',
                                    'director_club' => 'fa-crown',
                                    'director_tecnico' => 'fa-trophy'
                                ];
                                $color = $roleColors[$user->role] ?? 'dark';
                                $icon = $roleIcons[$user->role] ?? 'fa-user';
                            @endphp
                            <span class="badge badge-{{ $color }}">
                                <i class="fas {{ $icon }} mr-1"></i>{{ ucfirst(str_replace('_', ' ', $user->role)) }}
                            </span>
                        </div>

                        @if($user->profile && $user->profile->category)
                            <div class="mb-2">
                                <strong>Categoría:</strong>
                                <span class="badge badge-info"><i class="fas fa-layer-group mr-1"></i>{{ $user->profile->category->name }}</span>
                            </div>
                        @endif

                        <div class="mb-2">
                            <strong>Miembro desde:</strong> <i class="far fa-calendar-alt mr-1"></i>{{ $user->created_at->format('d/m/Y') }}
                        </div>
                    </div>
                </div>

                <hr>

                <h5><i class="fas fa-id-card mr-2"></i>Perfil Adicional</h5>
                @if($user->profile)
                    <div class="row">
                        @if($user->profile->position)
                            <div class="col-md-6 mb-2">
                                <i class="fas fa-map-marker-alt text-success mr-1"></i>
                                <strong>Posición:</strong> {{ $user->profile->position }}
                            </div>
                        @endif

                        @if($user->profile->secondary_position)
                            <div class="col-md-6 mb-2">
                                <i class="fas fa-map-marker text-info mr-1"></i>
                                <strong>Posición Secundaria:</strong> {{ $user->profile->secondary_position }}
                            </div>
                        @endif

                        @if($user->profile->player_number)
                            <div class="col-md-6 mb-2">
                                <i class="fas fa-hashtag text-primary mr-1"></i>
                                <strong>Número:</strong> #{{ $user->profile->player_number }}
                            </div>
                        @endif

                        @if($user->profile->weight)
                            <div class="col-md-6 mb-2">
                                <i class="fas fa-weight text-warning mr-1"></i>
                                <strong>Peso:</strong> {{ $user->profile->weight }} kg
                            </div>
                        @endif

                        @if($user->profile->height)
                            <div class="col-md-6 mb-2">
                                <i class="fas fa-ruler-vertical text-danger mr-1"></i>
                                <strong>Altura:</strong> {{ $user->profile->height }} cm
                            </div>
                        @endif

                        @if($user->profile->date_of_birth)
                            <div class="col-md-6 mb-2">
                                <i class="fas fa-birthday-cake text-pink mr-1"></i>
                                <strong>Fecha de Nacimiento:</strong> {{ \Carbon\Carbon::parse($user->profile->date_of_birth)->format('d/m/Y') }}
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-muted">No hay información de perfil adicional.</p>
                @endif
            </div>
        </div>

        <!-- Videos Recientes Subidos -->
        @if($user->uploadedVideos->count() > 0)
        <div class="card card-rugby mt-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-video mr-2"></i>Videos Recientes Subidos</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($user->uploadedVideos->take(5) as $video)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-play-circle text-success mr-2"></i>
                                    <strong>{{ $video->title }}</strong>
                                    @if($video->category)
                                        <span class="badge badge-secondary ml-2">{{ $video->category->name }}</span>
                                    @endif
                                </div>
                                <small class="text-muted">
                                    <i class="far fa-clock mr-1"></i>{{ $video->created_at->format('d/m/Y') }}
                                </small>
                            </div>
                            @if($video->description)
                                <small class="text-muted d-block mt-1">{{ Str::limit($video->description, 80) }}</small>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            @if($user->uploadedVideos->count() > 5)
                <div class="card-footer text-center">
                    <small class="text-muted">Mostrando 5 de {{ $user->uploadedVideos->count() }} videos totales</small>
                </div>
            @endif
        </div>
        @endif

        <!-- Videos Asignados (solo para jugadores) -->
        @if($user->role === 'jugador' && $user->assignedVideos->count() > 0)
        <div class="card card-warning mt-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bullseye mr-2"></i>Videos Asignados para Revisar</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($user->assignedVideos->take(5) as $assignment)
                        @if($assignment->video)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-tasks text-warning mr-2"></i>
                                    <strong>{{ $assignment->video->title }}</strong>
                                    @if($assignment->video->category)
                                        <span class="badge badge-secondary ml-2">{{ $assignment->video->category->name }}</span>
                                    @endif
                                </div>
                                <small class="text-muted">
                                    <i class="far fa-clock mr-1"></i>{{ $assignment->created_at->format('d/m/Y') }}
                                </small>
                            </div>
                        </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <!-- Panel de Actividad -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Actividad</h3>
            </div>
            <div class="card-body">
                <!-- Videos Subidos -->
                <div class="info-box bg-light mb-3">
                    <span class="info-box-icon bg-success">
                        <i class="fas fa-video"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Videos Subidos</span>
                        <span class="info-box-number">{{ $user->uploadedVideos->count() }}</span>
                    </div>
                </div>

                <!-- Comentarios Realizados -->
                <div class="info-box bg-light mb-3">
                    <span class="info-box-icon bg-info">
                        <i class="fas fa-comments"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Comentarios</span>
                        <span class="info-box-number">{{ $user->videoComments->count() }}</span>
                    </div>
                </div>

                <!-- Anotaciones Creadas -->
                <div class="info-box bg-light mb-3">
                    <span class="info-box-icon bg-warning">
                        <i class="fas fa-pen"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Anotaciones</span>
                        <span class="info-box-number">{{ $user->videoAnnotations->count() }}</span>
                    </div>
                </div>

                @if($user->role === 'jugador')
                    <!-- Videos Asignados (para jugadores) -->
                    <div class="info-box bg-light mb-3">
                        <span class="info-box-icon bg-primary">
                            <i class="fas fa-bullseye"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Videos Asignados</span>
                            <span class="info-box-number">{{ $user->assignedVideos->count() }}</span>
                        </div>
                    </div>
                @endif

                @if(in_array($user->role, ['entrenador', 'analista', 'director_tecnico']))
                    <!-- Asignaciones Creadas (para entrenadores/analistas) -->
                    <div class="info-box bg-light mb-3">
                        <span class="info-box-icon bg-danger">
                            <i class="fas fa-user-plus"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Videos Asignó</span>
                            <span class="info-box-number">{{ $user->assignedByMe->count() }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Acciones</h3>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-block mb-2">
                    <i class="fas fa-edit mr-2"></i>Editar Usuario
                </a>

                @if($user->id !== auth()->id())
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                      onsubmit="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="fas fa-trash mr-2"></i>Eliminar Usuario
                    </button>
                </form>
                @else
                <button class="btn btn-danger btn-block" disabled title="No puedes eliminar tu propia cuenta">
                    <i class="fas fa-ban mr-2"></i>No puedes eliminarte
                </button>
                @endif

                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-block mt-3">
                    <i class="fas fa-arrow-left mr-2"></i>Volver a Usuarios
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

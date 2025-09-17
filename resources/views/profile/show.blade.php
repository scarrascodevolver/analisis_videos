@extends('layouts.app')

@section('page_title', 'Mi Perfil')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Perfil</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-md-4">
        <!-- Profile Card -->
        <div class="card card-primary">
            <div class="card-header rugby-green">
                <h3 class="card-title">
                    <i class="fas fa-user"></i> Información Personal
                </h3>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    @if($user->profile && $user->profile->avatar)
                        <img src="{{ asset('storage/' . $user->profile->avatar) }}"
                             alt="Avatar"
                             class="img-circle elevation-2"
                             style="width: 120px; height: 120px; object-fit: cover;">
                    @else
                        <i class="fas fa-user-circle fa-8x text-muted"></i>
                    @endif
                </div>
                <h4 class="text-rugby">{{ $user->name }}</h4>
                <p class="text-muted">{{ ucfirst($user->role) }}</p>

                @if($user->profile && $user->profile->category)
                    <span class="badge badge-success">{{ $user->profile->category->name }}</span>
                @endif

                <div class="mt-3">
                    <a href="{{ route('profile.edit') }}" class="btn btn-rugby">
                        <i class="fas fa-edit"></i> Editar Perfil
                    </a>
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-phone"></i> Contacto
                </h3>
            </div>
            <div class="card-body">
                <strong><i class="fas fa-envelope mr-1"></i> Email</strong>
                <p class="text-muted">{{ $user->email }}</p>

                @if($user->phone)
                <strong><i class="fas fa-phone mr-1"></i> Teléfono</strong>
                <p class="text-muted">{{ $user->phone }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Player Information (for players only) -->
        @if($user->role === 'jugador' && $user->profile)
        <div class="card">
            <div class="card-header rugby-green">
                <h3 class="card-title">
                    <i class="fas fa-running"></i> Información de Jugador
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($user->profile->position)
                    <div class="col-sm-6">
                        <strong><i class="fas fa-map-marker-alt mr-1"></i> Posición Principal</strong>
                        <p class="text-muted">{{ $user->profile->position }}</p>
                    </div>
                    @endif

                    @if($user->profile->secondary_position)
                    <div class="col-sm-6">
                        <strong><i class="fas fa-map-marker-alt mr-1"></i> Posición Secundaria</strong>
                        <p class="text-muted">{{ $user->profile->secondary_position }}</p>
                    </div>
                    @endif

                    @if($user->profile->player_number)
                    <div class="col-sm-6">
                        <strong><i class="fas fa-hashtag mr-1"></i> Número</strong>
                        <p class="text-muted">#{{ $user->profile->player_number }}</p>
                    </div>
                    @endif

                    @if($user->profile->weight)
                    <div class="col-sm-6">
                        <strong><i class="fas fa-weight mr-1"></i> Peso</strong>
                        <p class="text-muted">{{ $user->profile->weight }} kg</p>
                    </div>
                    @endif

                    @if($user->profile->height)
                    <div class="col-sm-6">
                        <strong><i class="fas fa-ruler-vertical mr-1"></i> Altura</strong>
                        <p class="text-muted">{{ $user->profile->height }} cm</p>
                    </div>
                    @endif

                    @if($user->profile->date_of_birth)
                    <div class="col-sm-6">
                        <strong><i class="fas fa-birthday-cake mr-1"></i> Fecha de Nacimiento</strong>
                        <p class="text-muted">{{ $user->profile->date_of_birth->format('d/m/Y') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Coach Information (for coaches only) -->
        @if(in_array($user->role, ['entrenador', 'director_club']) && $user->profile)
        <div class="card">
            <div class="card-header rugby-green">
                <h3 class="card-title">
                    <i class="fas fa-chalkboard-teacher mr-1"></i> Información Técnica
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($user->profile->coaching_experience)
                    <div class="col-sm-12">
                        <strong><i class="fas fa-medal mr-1"></i> Experiencia</strong>
                        <p class="text-muted">{{ $user->profile->coaching_experience }}</p>
                    </div>
                    @endif

                    @if($user->profile->certifications)
                    <div class="col-sm-12">
                        <strong><i class="fas fa-certificate mr-1"></i> Certificaciones</strong>
                        <p class="text-muted">{{ $user->profile->certifications }}</p>
                    </div>
                    @endif

                    @if($user->profile->specializations)
                    <div class="col-sm-12">
                        <strong><i class="fas fa-star mr-1"></i> Especializaciones</strong>
                        <div class="mt-2">
                            @foreach($user->profile->specializations as $specialization)
                                <span class="badge badge-info mr-1">{{ $specialization }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- General Information -->
        @if($user->profile)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-1"></i> Información Adicional
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($user->profile->club_team_organization)
                    <div class="col-sm-6">
                        <strong><i class="fas fa-users mr-1"></i> Club/Organización</strong>
                        <p class="text-muted">{{ $user->profile->club_team_organization }}</p>
                    </div>
                    @endif

                    @if($user->profile->division_category)
                    <div class="col-sm-6">
                        <strong><i class="fas fa-layer-group mr-1"></i> División</strong>
                        <p class="text-muted">{{ $user->profile->division_category }}</p>
                    </div>
                    @endif

                    @if($user->profile->goals)
                    <div class="col-sm-12">
                        <strong><i class="fas fa-bullseye mr-1"></i> Objetivos</strong>
                        <p class="text-muted">{{ $user->profile->goals }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
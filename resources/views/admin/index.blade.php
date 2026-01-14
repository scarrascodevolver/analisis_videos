@extends('layouts.app')

@section('page_title', 'Mantenedor del Sistema')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Mantenedor</li>
@endsection

@section('main_content')
<div class="row">
    <!-- Categorías de Usuario -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['categories'] }}</h3>
                <p>Categorías de Usuario</p>
            </div>
            <div class="icon">
                <i class="fas fa-tags"></i>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="small-box-footer">
                Gestionar <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Situaciones Rugby -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['situations'] }}</h3>
                <p>Situaciones de Rugby</p>
            </div>
            <div class="icon">
                <i class="fas fa-football-ball"></i>
            </div>
            <a href="{{ route('admin.situations.index') }}" class="small-box-footer">
                Gestionar <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Usuarios -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['users'] }}</h3>
                <p>Usuarios</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="{{ route('admin.users.index') }}" class="small-box-footer">
                Gestionar <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Categorías de Clips -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['clip_categories'] ?? 0 }}</h3>
                <p>Categorías de Clips</p>
            </div>
            <div class="icon">
                <i class="fas fa-film"></i>
            </div>
            <a href="{{ route('admin.clip-categories.index') }}" class="small-box-footer">
                Gestionar <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">Módulos del Mantenedor</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-tags text-success"></i> Categorías de Usuario</h5>
                        <p class="text-muted">Gestiona las categorías de usuario (Juveniles, Adulta, Seniors, etc.). Estas se asignan a jugadores y determinan qué videos pueden ver.</p>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-rugby btn-sm mb-3">
                            <i class="fas fa-cog"></i> Gestionar Categorías
                        </a>
                    </div>

                    <div class="col-md-6">
                        <h5><i class="fas fa-football-ball text-warning"></i> Situaciones de Rugby</h5>
                        <p class="text-muted">Define situaciones de juego (Scrum, Lineout, Maul, etc.) para clasificar y filtrar videos de análisis.</p>
                        <a href="{{ route('admin.situations.index') }}" class="btn btn-warning btn-sm mb-3">
                            <i class="fas fa-cog"></i> Gestionar Situaciones
                        </a>
                    </div>

                    <div class="col-md-6">
                        <h5><i class="fas fa-users text-info"></i> Usuarios</h5>
                        <p class="text-muted">Gestiona usuarios del sistema: jugadores, entrenadores, analistas y staff. Asigna roles y categorías.</p>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-info btn-sm mb-3">
                            <i class="fas fa-cog"></i> Gestionar Usuarios
                        </a>
                    </div>

                    <div class="col-md-6">
                        <h5><i class="fas fa-ticket-alt text-secondary"></i> Código de Invitación</h5>
                        <p class="text-muted">Gestiona el código de invitación para que nuevos jugadores puedan registrarse en tu organización.</p>
                        <a href="{{ route('admin.organization') }}" class="btn btn-secondary btn-sm mb-3">
                            <i class="fas fa-cog"></i> Configurar Código
                        </a>
                    </div>

                    <div class="col-md-6">
                        <h5><i class="fas fa-film text-primary"></i> Categorías de Clips</h5>
                        <p class="text-muted">Configura la botonera de análisis de video. Define categorías como Try, Scrum, Lineout, etc., con teclas rápidas para marcar clips.</p>
                        <a href="{{ route('admin.clip-categories.index') }}" class="btn btn-primary btn-sm mb-3">
                            <i class="fas fa-cog"></i> Configurar Botonera
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

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

    <!-- Equipos -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['teams'] }}</h3>
                <p>Equipos</p>
            </div>
            <div class="icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <a href="{{ route('admin.teams.index') }}" class="small-box-footer">
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
                        <h5><i class="fas fa-shield-alt text-primary"></i> Equipos</h5>
                        <p class="text-muted">Administra equipos propios y rivales. Los videos se asocian con equipos para facilitar análisis y búsquedas.</p>
                        <a href="{{ route('admin.teams.index') }}" class="btn btn-primary btn-sm mb-3">
                            <i class="fas fa-cog"></i> Gestionar Equipos
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

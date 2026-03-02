@extends('layouts.app')

@section('page_title', 'Mantenedor del Sistema')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Mantenedor</li>
@endsection

@section('main_content')
<div class="row">
    <!-- Categorías -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success" style="padding-bottom: 0.5rem;">
            <div class="inner" style="padding: 1.5rem 1.5rem 1rem;">
                <h3 style="font-size: 2.5rem;">{{ $stats['categories'] }}</h3>
                <p style="font-size: 1rem;">Categorías</p>
            </div>
            <div class="icon">
                <i class="fas fa-tags"></i>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="small-box-footer">
                Gestionar <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Usuarios -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info" style="padding-bottom: 0.5rem;">
            <div class="inner" style="padding: 1.5rem 1.5rem 1rem;">
                <h3 style="font-size: 2.5rem;">{{ $stats['users'] }}</h3>
                <p style="font-size: 1rem;">Usuarios</p>
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
        <div class="small-box bg-primary" style="padding-bottom: 0.5rem;">
            <div class="inner" style="padding: 1.5rem 1.5rem 1rem;">
                <h3 style="font-size: 2.5rem;">{{ $stats['clip_categories'] ?? 0 }}</h3>
                <p style="font-size: 1rem;">Categorías de Clips</p>
            </div>
            <div class="icon">
                <i class="fas fa-film"></i>
            </div>
            <a href="{{ route('admin.clip-categories.index') }}" class="small-box-footer">
                Gestionar <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Código de Registro -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary" style="padding-bottom: 0.5rem;">
            <div class="inner" style="padding: 1.5rem 1.5rem 1rem;">
                <h3 style="font-size: 2.5rem;"><i class="fas fa-key" style="font-size: 2rem;"></i></h3>
                <p style="font-size: 1rem;">Código de Registro</p>
            </div>
            <div class="icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <a href="{{ route('admin.organization') }}" class="small-box-footer">
                Configurar <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>
@endsection

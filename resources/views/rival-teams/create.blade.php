@extends('layouts.app')

@section('page_title', 'Nuevo Equipo Rival')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('rival-teams.index') }}">Equipos Rivales</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@section('main_content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: var(--color-primary, #005461); color: white;">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-plus"></i> Nuevo Equipo Rival
                    </h3>
                </div>
                <form action="{{ route('rival-teams.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <!-- Nombre -->
                        <div class="form-group">
                            <label for="name">Nombre del Equipo <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Ej: Old Navy RC"
                                   required
                                   autofocus>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">Nombre completo del equipo rival</small>
                        </div>

                        <!-- Código -->
                        <div class="form-group">
                            <label for="code">Código o Siglas</label>
                            <input type="text"
                                   class="form-control @error('code') is-invalid @enderror"
                                   id="code"
                                   name="code"
                                   value="{{ old('code') }}"
                                   placeholder="Ej: ONRC"
                                   maxlength="10">
                            @error('code')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">Abreviatura o código del equipo (opcional)</small>
                        </div>

                        <!-- Ciudad -->
                        <div class="form-group">
                            <label for="city">Ciudad</label>
                            <input type="text"
                                   class="form-control @error('city') is-invalid @enderror"
                                   id="city"
                                   name="city"
                                   value="{{ old('city') }}"
                                   placeholder="Ej: San Isidro"
                                   maxlength="100">
                            @error('city')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">Ciudad o localidad del equipo (opcional)</small>
                        </div>

                        <!-- Notas -->
                        <div class="form-group">
                            <label for="notes">Notas</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                      id="notes"
                                      name="notes"
                                      rows="3"
                                      maxlength="1000"
                                      placeholder="Información adicional sobre el equipo...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">Información adicional (opcional)</small>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('rival-teams.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Equipo Rival
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

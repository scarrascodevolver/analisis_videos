@extends('layouts.app')

@section('page_title', 'Gestión de Equipos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item active">Equipos</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <div class="card card-rugby">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Equipos</h3>
                    <a href="{{ route('admin.teams.create') }}" class="btn btn-rugby btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Equipo
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($teams->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No hay equipos creados. Crea el primer equipo ahora.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="rugby-green">
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="30%">Nombre</th>
                                    <th width="10%">Abreviación</th>
                                    <th width="15%" class="text-center">Tipo</th>
                                    <th width="15%" class="text-center">Videos Analizados</th>
                                    <th width="15%" class="text-center">Videos como Rival</th>
                                    <th width="10%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teams as $team)
                                    <tr>
                                        <td>{{ $team->id }}</td>
                                        <td><strong>{{ $team->name }}</strong></td>
                                        <td>{{ $team->abbreviation ?: '-' }}</td>
                                        <td class="text-center">
                                            @if($team->is_own_team)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle"></i> Propio
                                                </span>
                                            @else
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-shield-alt"></i> Rival
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-primary">{{ $team->analyzed_videos_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{ $team->rival_videos_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.teams.edit', $team) }}"
                                                   class="btn btn-warning btn-sm"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.teams.destroy', $team) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar este equipo?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-danger btn-sm"
                                                            title="Eliminar"
                                                            {{ ($team->analyzed_videos_count > 0 || $team->rival_videos_count > 0) ? 'disabled' : '' }}>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
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

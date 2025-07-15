@extends('layouts.app')

@section('page_title', 'Gestión de Asignaciones')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Asignaciones</li>
@endsection

@section('main_content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tasks"></i>
                        Asignaciones de Videos
                    </h3>
                    <div class="card-tools">
                        @if(auth()->user()->role === 'analista')
                            <a href="{{ route('analyst.assignments.create') }}" class="btn btn-rugby">
                                <i class="fas fa-plus"></i> Nueva Asignación
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($assignments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Video</th>
                                        <th>Jugador</th>
                                        <th>Analista</th>
                                        <th>Prioridad</th>
                                        <th>Estado</th>
                                        <th>Fecha Límite</th>
                                        <th>Progreso</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assignments as $assignment)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-video text-primary mr-2"></i>
                                                    <div>
                                                        <strong>{{ $assignment->video->title }}</strong><br>
                                                        <small class="text-muted">
                                                            {{ $assignment->video->analyzedTeam->name }}
                                                            @if($assignment->video->rivalTeam)
                                                                vs {{ $assignment->video->rivalTeam->name }}
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user text-info mr-2"></i>
                                                    {{ $assignment->player->name }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user-tie text-success mr-2"></i>
                                                    {{ $assignment->analyst->name }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ 
                                                    $assignment->priority === 'critica' ? 'danger' : 
                                                    ($assignment->priority === 'alta' ? 'warning' : 
                                                    ($assignment->priority === 'media' ? 'info' : 'secondary')) 
                                                }}">
                                                    {{ ucfirst($assignment->priority) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ 
                                                    $assignment->status === 'completado' ? 'success' : 
                                                    ($assignment->status === 'en_progreso' ? 'primary' : 
                                                    ($assignment->status === 'asignado' ? 'warning' : 'secondary')) 
                                                }}">
                                                    {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="{{ $assignment->due_date->isPast() && $assignment->status !== 'completado' ? 'text-danger' : '' }}">
                                                    {{ $assignment->due_date->format('d/m/Y') }}
                                                </span>
                                                @if($assignment->due_date->isPast() && $assignment->status !== 'completado')
                                                    <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Vencido</small>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $progress = 0;
                                                    if ($assignment->status === 'asignado') $progress = 25;
                                                    elseif ($assignment->status === 'en_progreso') $progress = 75;
                                                    elseif ($assignment->status === 'completado') $progress = 100;
                                                @endphp
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-{{ $progress === 100 ? 'success' : 'primary' }}" 
                                                         role="progressbar" style="width: {{ $progress }}%">
                                                        {{ $progress }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('analyst.assignments.show', $assignment) }}" 
                                                       class="btn btn-sm btn-outline-primary" title="Ver Detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(auth()->user()->role === 'analista' && $assignment->status !== 'completado')
                                                        <a href="{{ route('analyst.assignments.edit', $assignment) }}" 
                                                           class="btn btn-sm btn-outline-warning" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endif
                                                    @if(auth()->id() === $assignment->player_id && $assignment->status === 'asignado')
                                                        <form method="POST" action="{{ route('analyst.assignments.playerAccept', $assignment) }}" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success" title="Aceptar Asignación">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer">
                            {{ $assignments->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay asignaciones disponibles</h5>
                            @if(auth()->user()->role === 'analista')
                                <p class="text-muted">Comienza creando una nueva asignación de video</p>
                                <a href="{{ route('analyst.assignments.create') }}" class="btn btn-rugby">
                                    <i class="fas fa-plus"></i> Nueva Asignación
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mt-4">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $assignments->where('status', 'asignado')->count() }}</h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $assignments->where('status', 'en_progreso')->count() }}</h3>
                    <p>En Progreso</p>
                </div>
                <div class="icon">
                    <i class="fas fa-play"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $assignments->where('status', 'completado')->count() }}</h3>
                    <p>Completadas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $assignments->filter(function($a) { return $a->due_date->isPast() && $a->status !== 'completado'; })->count() }}</h3>
                    <p>Vencidas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
@endsection
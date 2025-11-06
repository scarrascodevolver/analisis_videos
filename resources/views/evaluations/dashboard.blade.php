@extends('layouts.app')

@section('page_title', Auth::user()->role === 'jugador' ? 'Mis Resultados' : 'Resultados de Evaluaciones')

@section('breadcrumbs')
    <li class="breadcrumb-item active">{{ Auth::user()->role === 'jugador' ? 'Mis Resultados' : 'Resultados de Evaluaciones' }}</li>
@endsection

@section('main_content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: #1e4d2b; color: white;">
                    <h3 class="card-title mb-0">
                        <i class="fas {{ Auth::user()->role === 'jugador' ? 'fa-chart-line' : 'fa-chart-bar' }}"></i>
                        {{ Auth::user()->role === 'jugador' ? 'Mis Resultados de Evaluación' : 'Resultados de Evaluaciones' }}
                    </h3>
                    <small class="d-block mt-1">
                        {{ Auth::user()->role === 'jugador'
                            ? 'Visualiza cómo te han evaluado tus compañeros de categoría'
                            : 'Visualiza el desempeño de los jugadores según evaluaciones de compañeros' }}
                    </small>
                </div>
                <div class="card-body">
                    <!-- Filtros (solo para entrenadores/analistas) -->
                    @if(in_array(Auth::user()->role, ['entrenador', 'analista']))
                    <form method="GET" action="{{ route('evaluations.dashboard') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="category_id">Filtrar por Categoría:</label>
                                <select name="category_id" id="category_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">Todas las categorías</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                    @endif

                    <!-- Tabla de resultados -->
                    @if($playersStats->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead style="background-color: #1e4d2b; color: white;">
                                    <tr>
                                        @if(in_array(Auth::user()->role, ['entrenador', 'analista']))
                                        <th>#</th>
                                        @endif
                                        <th>Jugador</th>
                                        <th>Posición</th>
                                        <th class="text-center">Promedio</th>
                                        <th class="text-center">Puntaje Total</th>
                                        <th class="text-center">Evaluaciones</th>
                                        <th class="text-center">Completado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($playersStats as $index => $stat)
                                    <tr>
                                        @if(in_array(Auth::user()->role, ['entrenador', 'analista']))
                                        <td>{{ $index + 1 }}</td>
                                        @endif
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($stat['player']->profile && $stat['player']->profile->avatar)
                                                    <img src="{{ asset('storage/' . $stat['player']->profile->avatar) }}"
                                                         alt="Avatar"
                                                         class="img-circle elevation-2 mr-2"
                                                         style="width: 35px; height: 35px; object-fit: cover;">
                                                @else
                                                    <i class="fas fa-user-circle fa-2x text-muted mr-2"></i>
                                                @endif
                                                <strong>{{ $stat['player']->name }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ $stat['player']->profile->position ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($stat['evaluations_count'] > 0)
                                                <span class="badge badge-lg" style="
                                                    background-color: {{ $stat['average_score'] >= 7 ? '#1e4d2b' : ($stat['average_score'] >= 5 ? '#ffc107' : '#dc3545') }};
                                                    color: white;
                                                    font-size: 1.1rem;
                                                    padding: 0.4rem 0.8rem;
                                                ">
                                                    {{ number_format($stat['average_score'], 1) }}
                                                </span>
                                            @else
                                                <span class="text-muted">Sin evaluaciones</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($stat['evaluations_count'] > 0)
                                                <div>
                                                    <strong style="color: {{ $stat['total_points_percentage'] >= 70 ? '#1e4d2b' : ($stat['total_points_percentage'] >= 50 ? '#ffc107' : '#dc3545') }};">
                                                        {{ $stat['total_points_avg'] }}/{{ $stat['total_points_max'] }}
                                                    </strong>
                                                    <br>
                                                    <small class="text-muted">({{ number_format($stat['total_points_percentage'], 1) }}%)</small>
                                                </div>
                                            @else
                                                <span class="text-muted">0/280<br><small>(0%)</small></span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-secondary">
                                                {{ $stat['evaluations_count'] }}/{{ $stat['total_possible'] }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar"
                                                     style="width: {{ $stat['completion_percentage'] }}%;
                                                            background-color: {{ $stat['completion_percentage'] >= 75 ? '#1e4d2b' : ($stat['completion_percentage'] >= 50 ? '#ffc107' : '#dc3545') }};">
                                                    {{ $stat['completion_percentage'] }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($stat['evaluations_count'] > 0)
                                                <a href="{{ route('evaluations.show', $stat['player']->id) }}"
                                                   class="btn btn-sm text-white"
                                                   style="background-color: #1e4d2b;">
                                                    <i class="fas fa-eye"></i> Ver Detalle
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- Modal de Detalle -->
                                    <div class="modal fade" id="detailModal{{ $stat['player']->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header" style="background-color: #1e4d2b; color: white;">
                                                    <h5 class="modal-title">
                                                        Evaluaciones de {{ $stat['player']->name }}
                                                    </h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Promedio General:</strong>
                                                        <span class="badge" style="background-color: #1e4d2b; color: white; font-size: 1.2rem;">
                                                            {{ number_format($stat['average_score'], 2) }}
                                                        </span>
                                                    </p>
                                                    <p><strong>Total de Evaluaciones:</strong> {{ $stat['evaluations_count'] }}</p>

                                                    <hr>

                                                    <h6>Lista de Evaluadores:</h6>
                                                    <ul class="list-group">
                                                        @foreach($stat['player']->receivedEvaluations as $evaluation)
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <span>
                                                                <i class="fas fa-user"></i>
                                                                {{ $evaluation->evaluator->name }}
                                                            </span>
                                                            <span class="badge badge-primary badge-pill">
                                                                {{ number_format($evaluation->total_score, 1) }}
                                                            </span>
                                                        </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No hay jugadores en esta categoría o aún no hay evaluaciones registradas.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
.table-responsive {
    overflow-x: auto;
}

.badge-lg {
    font-size: 1.1rem;
    padding: 0.4rem 0.8rem;
}

@media (max-width: 768px) {
    .table {
        font-size: 0.85rem;
    }

    .badge-lg {
        font-size: 0.9rem;
        padding: 0.3rem 0.6rem;
    }
}
</style>
@endsection

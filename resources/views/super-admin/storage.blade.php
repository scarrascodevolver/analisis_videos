@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Almacenamiento</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">
                <i class="fas fa-database text-info mr-2"></i>
                Uso de Almacenamiento
            </h1>
            <p class="text-muted">Estadísticas de uso de DigitalOcean Spaces por organización</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Almacenamiento Total</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($totalStorage / 1073741824, 2) }} GB
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hdd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Videos Totales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalVideos }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-video fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Promedio por Video</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $totalVideos > 0 ? number_format(($totalStorage / $totalVideos) / 1048576, 0) : 0 }} MB
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Organizaciones</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $storageByOrg->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Storage by Organization Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie mr-2"></i>Uso por Organización
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Organización</th>
                                    <th class="text-center">Videos</th>
                                    <th class="text-right">Almacenamiento</th>
                                    <th class="text-right">Promedio</th>
                                    <th style="width: 30%">Uso</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($storageByOrg as $org)
                                @php
                                    $percentage = $totalStorage > 0 ? ($org->total_size / $totalStorage) * 100 : 0;
                                    $sizeGB = $org->total_size / 1073741824;
                                    $sizeMB = $org->total_size / 1048576;
                                    $avgMB = $org->avg_size / 1048576;
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $org->name }}</strong>
                                        <br><small class="text-muted">{{ $org->slug }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $org->video_count }}</span>
                                    </td>
                                    <td class="text-right">
                                        @if($sizeGB >= 1)
                                            <strong>{{ number_format($sizeGB, 2) }} GB</strong>
                                        @else
                                            {{ number_format($sizeMB, 0) }} MB
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        {{ number_format($avgMB, 0) }} MB
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $percentage > 50 ? 'danger' : ($percentage > 25 ? 'warning' : 'success') }}"
                                                 role="progressbar"
                                                 style="width: {{ max($percentage, 1) }}%"
                                                 aria-valuenow="{{ $percentage }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                                {{ number_format($percentage, 1) }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td><strong>TOTAL</strong></td>
                                    <td class="text-center"><strong>{{ $totalVideos }}</strong></td>
                                    <td class="text-right"><strong>{{ number_format($totalStorage / 1073741824, 2) }} GB</strong></td>
                                    <td class="text-right">
                                        <strong>{{ $totalVideos > 0 ? number_format(($totalStorage / $totalVideos) / 1048576, 0) : 0 }} MB</strong>
                                    </td>
                                    <td><strong>100%</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($orphanVideos > 0)
    <!-- Orphan Videos Warning -->
    <div class="row">
        <div class="col-12">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Videos sin organización:</strong> {{ $orphanVideos }} videos ({{ number_format($orphanSize / 1048576, 0) }} MB) no tienen organización asignada.
            </div>
        </div>
    </div>
    @endif

    <!-- Storage Visual Chart -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar mr-2"></i>Top 5 Organizaciones por Uso
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($storageByOrg->take(5) as $org)
                    @php
                        $percentage = $totalStorage > 0 ? ($org->total_size / $totalStorage) * 100 : 0;
                        $sizeMB = $org->total_size / 1048576;
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ $org->name }}</span>
                            <span class="text-muted">{{ number_format($sizeMB, 0) }} MB</span>
                        </div>
                        <div class="progress" style="height: 12px;">
                            <div class="progress-bar bg-primary" style="width: {{ max($percentage, 2) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle mr-2"></i>Información
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-cloud text-info mr-2"></i>
                            <strong>Proveedor:</strong> DigitalOcean Spaces
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-dollar-sign text-success mr-2"></i>
                            <strong>Costo estimado:</strong> ${{ number_format(($totalStorage / 1073741824) * 0.02, 2) }}/mes
                            <small class="text-muted">(~$0.02/GB)</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-film text-primary mr-2"></i>
                            <strong>Formato:</strong> MP4 (comprimido)
                        </li>
                        <li>
                            <i class="fas fa-compress text-warning mr-2"></i>
                            <strong>Compresión:</strong> H.264/AAC
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <a href="{{ route('super-admin.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver al Dashboard
            </a>
        </div>
    </div>
</div>
@endsection

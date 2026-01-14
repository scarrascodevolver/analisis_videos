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
                                Costo Total Mensual</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format(($totalStorage / 1073741824) * 0.02, 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                        <i class="fas fa-building mr-2"></i>Uso por Organización
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
                                    <th class="text-right">Costo/Mes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($storageByOrg as $org)
                                @php
                                    $sizeGB = $org->total_size / 1073741824;
                                    $sizeMB = $org->total_size / 1048576;
                                    $costPerMonth = $sizeGB * 0.02; // $0.02 por GB
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $org->name }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $org->video_count }}</span>
                                    </td>
                                    <td class="text-right">
                                        <strong>{{ number_format($sizeGB, 2) }} GB</strong>
                                        <br><small class="text-muted">{{ number_format($sizeMB, 0) }} MB</small>
                                    </td>
                                    <td class="text-right">
                                        <strong class="text-success">${{ number_format($costPerMonth, 2) }}</strong>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td><strong>TOTAL</strong></td>
                                    <td class="text-center"><strong>{{ $totalVideos }}</strong></td>
                                    <td class="text-right"><strong>{{ number_format($totalStorage / 1073741824, 2) }} GB</strong></td>
                                    <td class="text-right"><strong class="text-success">${{ number_format(($totalStorage / 1073741824) * 0.02, 2) }}</strong></td>
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

    <!-- Resumen de Costos -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-dollar-sign mr-2"></i>Resumen de Costos Mensuales
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($storageByOrg as $org)
                    @php
                        $sizeGB = $org->total_size / 1073741824;
                        $costPerMonth = $sizeGB * 0.02;
                    @endphp
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong>{{ $org->name }}</strong>
                            <br><small class="text-muted">{{ $org->video_count }} videos</small>
                        </div>
                        <div class="text-right">
                            <span class="text-muted">{{ number_format($sizeGB, 2) }} GB</span>
                            <br><strong class="text-success">${{ number_format($costPerMonth, 2) }}/mes</strong>
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
                        <i class="fas fa-info-circle mr-2"></i>Información de Costos
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-cloud mr-2"></i>
                        <strong>DigitalOcean Spaces</strong> - $0.02 USD por GB/mes
                    </div>
                    <table class="table table-sm mb-0">
                        <tr>
                            <td>Almacenamiento Total:</td>
                            <td class="text-right"><strong>{{ number_format($totalStorage / 1073741824, 2) }} GB</strong></td>
                        </tr>
                        <tr>
                            <td>Videos Totales:</td>
                            <td class="text-right"><strong>{{ $totalVideos }}</strong></td>
                        </tr>
                        <tr>
                            <td>Promedio por Video:</td>
                            <td class="text-right"><strong>{{ $totalVideos > 0 ? number_format(($totalStorage / $totalVideos) / 1048576, 0) : 0 }} MB</strong></td>
                        </tr>
                        <tr class="bg-light">
                            <td><strong>Costo Mensual Total:</strong></td>
                            <td class="text-right"><strong class="text-success">${{ number_format(($totalStorage / 1073741824) * 0.02, 2) }} USD</strong></td>
                        </tr>
                    </table>
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

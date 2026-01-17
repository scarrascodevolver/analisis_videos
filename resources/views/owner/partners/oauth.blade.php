@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-link text-info mr-2"></i>
                Conectar Cuentas de Pago
            </h1>
            <p class="text-muted">Conecta las cuentas de Mercado Pago de cada socio para recibir pagos automáticos</p>
        </div>
    </div>

    <!-- Navegación del panel -->
    <div class="row mb-4">
        <div class="col-12">
            <nav class="nav nav-pills">
                <a class="nav-link" href="{{ route('owner.payments.index') }}">
                    <i class="fas fa-chart-line mr-1"></i> Dashboard
                </a>
                <a class="nav-link" href="{{ route('owner.splits.index') }}">
                    <i class="fas fa-share-alt mr-1"></i> Splits
                </a>
                <a class="nav-link" href="{{ route('owner.partners.index') }}">
                    <i class="fas fa-users mr-1"></i> Socios
                </a>
                <a class="nav-link active" href="{{ route('owner.partners.oauth.index') }}">
                    <i class="fas fa-link mr-1"></i> Conectar MP
                </a>
                <a class="nav-link" href="{{ route('owner.plans.index') }}">
                    <i class="fas fa-tags mr-1"></i> Planes
                </a>
            </nav>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    <!-- Explicación -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-body">
                    <h5 class="card-title text-info">
                        <i class="fas fa-info-circle mr-2"></i>¿Cómo funciona el Split Automático?
                    </h5>
                    <p class="mb-2">
                        Para que cada socio reciba automáticamente su porcentaje de cada pago:
                    </p>
                    <ol class="mb-0">
                        <li>Cada socio debe tener una cuenta de <strong>Mercado Pago</strong></li>
                        <li>Haz clic en "Conectar" para autorizar la cuenta de cada socio</li>
                        <li>Una vez conectados, los pagos se dividirán automáticamente</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado de conexión de cada socio -->
    <div class="row">
        @foreach($partners as $partner)
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100 {{ $partner->hasMercadoPagoConnected() ? 'border-success' : 'border-warning' }}">
                <div class="card-header {{ $partner->hasMercadoPagoConnected() ? 'bg-success' : 'bg-warning' }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user mr-2"></i>{{ $partner->name }}
                        </h5>
                        <span class="badge badge-light">{{ $partner->split_percentage }}%</span>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-3">
                        <tr>
                            <td class="text-muted" width="40%">Email:</td>
                            <td>{{ $partner->email }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Rol:</td>
                            <td>
                                <span class="badge badge-{{ $partner->role === 'owner' ? 'danger' : 'info' }}">
                                    {{ $partner->role === 'owner' ? 'Propietario' : 'Socio' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Mercado Pago:</td>
                            <td>
                                @if($partner->hasMercadoPagoConnected())
                                <span class="text-success">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Conectado
                                </span>
                                @if($partner->mercadopago_email)
                                <br><small class="text-muted">{{ $partner->mercadopago_email }}</small>
                                @endif
                                @else
                                <span class="text-warning">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    No conectado
                                </span>
                                @endif
                            </td>
                        </tr>
                        @if($partner->hasMercadoPagoConnected())
                        <tr>
                            <td class="text-muted">ID de Usuario:</td>
                            <td><code>{{ $partner->mp_user_id }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Token válido:</td>
                            <td>
                                @if($partner->isMpTokenValid())
                                <span class="text-success">
                                    <i class="fas fa-check mr-1"></i>
                                    Hasta {{ $partner->mp_token_expires_at->format('d/m/Y H:i') }}
                                </span>
                                @else
                                <span class="text-danger">
                                    <i class="fas fa-times mr-1"></i>
                                    Expirado
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endif
                    </table>

                    <div class="d-flex justify-content-between">
                        @if($partner->hasMercadoPagoConnected())
                            @if(!$partner->isMpTokenValid())
                            <a href="{{ route('owner.partners.oauth.refresh', $partner) }}"
                               class="btn btn-warning">
                                <i class="fas fa-sync mr-1"></i> Refrescar Token
                            </a>
                            @else
                            <span class="text-success">
                                <i class="fas fa-check-circle mr-1"></i> Listo para recibir pagos
                            </span>
                            @endif

                            <form action="{{ route('owner.partners.oauth.disconnect', $partner) }}"
                                  method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Desconectar cuenta de Mercado Pago?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-unlink mr-1"></i> Desconectar
                                </button>
                            </form>
                        @else
                            <a href="{{ route('owner.partners.oauth.connect', $partner) }}"
                               class="btn btn-primary btn-lg btn-block">
                                <i class="fab fa-mercadopago mr-2"></i>
                                Conectar Mercado Pago
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Resumen -->
    <div class="row mt-4">
        <div class="col-12">
            @php
                $connectedCount = $partners->filter(fn($p) => $p->hasMercadoPagoConnected())->count();
                $totalCount = $partners->count();
                $allConnected = $connectedCount === $totalCount;
            @endphp

            @if($allConnected)
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-2"></i>
                <strong>¡Todos los socios están conectados!</strong>
                Los pagos se dividirán automáticamente según los porcentajes configurados.
            </div>
            @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>{{ $connectedCount }} de {{ $totalCount }} socios conectados.</strong>
                Conecta todas las cuentas para habilitar el split automático.
                Mientras tanto, los pagos irán a la cuenta principal y deberás transferir manualmente.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

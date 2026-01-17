@extends('layouts.app')

@section('main_content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            @endif

            @if(request('cancelled'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle mr-2"></i>
                El pago fue cancelado. Puedes intentarlo de nuevo cuando quieras.
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            @endif

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Checkout - {{ $plan->name }}
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Resumen del pedido -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-muted mb-3">Resumen del pedido</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td>Plan:</td>
                                    <td class="text-right"><strong>{{ $plan->name }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Duración:</td>
                                    <td class="text-right">{{ $plan->duration_months }} {{ $plan->duration_months == 1 ? 'mes' : 'meses' }}</td>
                                </tr>
                                <tr>
                                    <td>Organización:</td>
                                    <td class="text-right">{{ $organization->name }}</td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>Total:</strong></td>
                                    <td class="text-right">
                                        <strong class="h4">{{ $plan->getFormattedPrice($currency) }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-muted mb-3">Características incluidas</h5>
                            @if($plan->features && count($plan->features) > 0)
                            <ul class="list-unstyled">
                                @foreach($plan->features as $feature)
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                    {{ $feature }}
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted">Acceso completo a todas las funciones de la plataforma.</p>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <!-- Selector de método de pago -->
                    <h5 class="text-muted mb-3">Método de pago</h5>

                    @if($provider === 'paypal' || in_array($currency, ['USD', 'EUR']))
                    <!-- PayPal -->
                    <div class="card mb-3 border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <img src="https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png"
                                         alt="PayPal" height="25" class="mr-3">
                                    <span>Pagar con PayPal</span>
                                </div>
                                <button type="button" id="paypal-button" class="btn btn-primary btn-lg">
                                    <i class="fab fa-paypal mr-2"></i>
                                    Pagar {{ $plan->getFormattedPrice($currency) }}
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Pago seguro. Serás redirigido a PayPal para completar tu compra.
                            </small>
                        </div>
                    </div>
                    @endif

                    @if($provider === 'mercadopago' || in_array($currency, ['CLP', 'PEN', 'BRL']))
                    <!-- Mercado Pago -->
                    <div class="card mb-3 border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <img src="https://http2.mlstatic.com/frontend-assets/mp-web-navigation/ui-navigation/5.21.22/mercadopago/logo__large@2x.png"
                                         alt="Mercado Pago" height="25" class="mr-3">
                                    <span>Pagar con Mercado Pago</span>
                                </div>
                                <button type="button" id="mercadopago-button" class="btn btn-info btn-lg">
                                    <i class="fas fa-credit-card mr-2"></i>
                                    Pagar {{ $plan->getFormattedPrice($currency) }}
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Tarjeta de crédito/débito, transferencia bancaria y más opciones locales.
                            </small>
                        </div>
                    </div>
                    @endif

                    <div class="text-center mt-4">
                        <a href="{{ route('subscription.pricing') }}" class="text-muted">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Volver a los planes
                        </a>
                    </div>
                </div>
            </div>

            <!-- Información de seguridad -->
            <div class="text-center mt-4">
                <p class="text-muted small">
                    <i class="fas fa-lock mr-1"></i>
                    Tu información de pago está protegida con encriptación SSL de 256 bits.
                    <br>
                    No almacenamos datos de tarjetas de crédito en nuestros servidores.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white;">
        <div class="spinner-border text-light mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="sr-only">Cargando...</span>
        </div>
        <h4>Procesando pago...</h4>
        <p>Por favor no cierres esta ventana</p>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paypalBtn = document.getElementById('paypal-button');
    const mpBtn = document.getElementById('mercadopago-button');
    const overlay = document.getElementById('loading-overlay');

    if (paypalBtn) {
        paypalBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creando orden...';
            overlay.style.display = 'block';

            fetch('{{ route("subscription.paypal.create", $plan) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    currency: '{{ $currency }}'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                if (data.approve_url) {
                    window.location.href = data.approve_url;
                } else {
                    throw new Error('No se recibió URL de aprobación');
                }
            })
            .catch(error => {
                overlay.style.display = 'none';
                paypalBtn.disabled = false;
                paypalBtn.innerHTML = '<i class="fab fa-paypal mr-2"></i> Pagar {{ $plan->getFormattedPrice($currency) }}';
                alert('Error: ' + error.message);
            });
        });
    }

    if (mpBtn) {
        mpBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creando orden...';
            overlay.style.display = 'block';

            fetch('{{ route("subscription.mercadopago.create", $plan) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    currency: '{{ $currency }}'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                if (data.init_point) {
                    window.location.href = data.init_point;
                } else {
                    throw new Error('No se recibió URL de pago');
                }
            })
            .catch(error => {
                overlay.style.display = 'none';
                mpBtn.disabled = false;
                mpBtn.innerHTML = '<i class="fas fa-credit-card mr-2"></i> Pagar {{ $plan->getFormattedPrice($currency) }}';
                alert('Error: ' + error.message);
            });
        });
    }
});
</script>
@endpush
@endsection

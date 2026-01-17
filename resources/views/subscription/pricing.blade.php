@extends('layouts.app-auth')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 font-weight-bold">Planes de Suscripción</h1>
        <p class="lead text-muted">Elige el plan perfecto para tu organización</p>

        <!-- Selector de moneda -->
        <div class="btn-group mt-3" role="group">
            <a href="?currency=CLP" class="btn btn-{{ $currency === 'CLP' ? 'primary' : 'outline-primary' }}">
                CLP (Chile)
            </a>
            <a href="?currency=USD" class="btn btn-{{ $currency === 'USD' ? 'primary' : 'outline-primary' }}">
                USD
            </a>
            <a href="?currency=EUR" class="btn btn-{{ $currency === 'EUR' ? 'primary' : 'outline-primary' }}">
                EUR
            </a>
            <a href="?currency=PEN" class="btn btn-{{ $currency === 'PEN' ? 'primary' : 'outline-primary' }}">
                PEN (Perú)
            </a>
            <a href="?currency=BRL" class="btn btn-{{ $currency === 'BRL' ? 'primary' : 'outline-primary' }}">
                BRL (Brasil)
            </a>
        </div>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    <div class="row justify-content-center">
        @foreach($plans as $plan)
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 shadow-lg {{ $plan->duration_months == 6 ? 'border-primary' : '' }}">
                @if($plan->duration_months == 6)
                <div class="card-header bg-primary text-white text-center py-2">
                    <small class="font-weight-bold">MÁS POPULAR</small>
                </div>
                @endif

                <div class="card-body d-flex flex-column">
                    <h3 class="card-title text-center">{{ $plan->name }}</h3>

                    <div class="text-center my-4">
                        <span class="display-4 font-weight-bold">
                            {{ $plan->getFormattedPrice($currency) }}
                        </span>
                        <span class="text-muted">
                            / {{ $plan->duration_months }} {{ $plan->duration_months == 1 ? 'mes' : 'meses' }}
                        </span>
                    </div>

                    @if($plan->duration_months > 1)
                    <p class="text-center text-success mb-3">
                        <i class="fas fa-tag"></i>
                        {{ $plan->getFormattedPrice($currency) }} por {{ $plan->duration_months }} meses
                        <br>
                        <small class="text-muted">
                            ~{{ number_format($plan->getPriceForCurrency($currency) / $plan->duration_months, 0) }}/mes
                        </small>
                    </p>
                    @endif

                    <p class="text-muted text-center mb-4">{{ $plan->description }}</p>

                    @if($plan->features && count($plan->features) > 0)
                    <ul class="list-unstyled mb-4 flex-grow-1">
                        @foreach($plan->features as $feature)
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    @endif

                    <div class="mt-auto">
                        @auth
                        <a href="{{ route('subscription.checkout', ['plan' => $plan, 'currency' => $currency]) }}"
                           class="btn btn-{{ $plan->duration_months == 6 ? 'primary' : 'outline-primary' }} btn-lg btn-block">
                            Suscribirse
                        </a>
                        @else
                        <a href="{{ route('login', ['redirect' => route('subscription.checkout', ['plan' => $plan, 'currency' => $currency])]) }}"
                           class="btn btn-{{ $plan->duration_months == 6 ? 'primary' : 'outline-primary' }} btn-lg btn-block">
                            Iniciar Sesión para Suscribirse
                        </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="text-center mt-5">
        <p class="text-muted">
            <i class="fas fa-lock mr-2"></i>
            Pagos seguros procesados por PayPal y Mercado Pago
        </p>
        <div class="d-flex justify-content-center align-items-center mt-3">
            <img src="https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png" alt="PayPal" height="30" class="mx-3">
            <img src="https://http2.mlstatic.com/frontend-assets/mp-web-navigation/ui-navigation/5.21.22/mercadopago/logo__large@2x.png" alt="Mercado Pago" height="30" class="mx-3">
        </div>
    </div>
</div>

<style>
.card:hover {
    transform: translateY(-5px);
    transition: transform 0.3s ease;
}
.display-4 {
    font-size: 2.5rem;
}
</style>
@endsection

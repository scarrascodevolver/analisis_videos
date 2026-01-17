@extends('layouts.app')

@section('main_content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-lg border-success">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>

                    <h2 class="text-success mb-3">¡Pago Completado!</h2>
                    <p class="lead text-muted mb-4">
                        Tu suscripción ha sido activada exitosamente.
                    </p>

                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Detalles de tu suscripción</h5>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">Plan:</td>
                                    <td class="text-right"><strong>{{ $subscription->plan->name }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Organización:</td>
                                    <td class="text-right">{{ $subscription->organization->name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Inicio:</td>
                                    <td class="text-right">{{ $subscription->starts_at->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Vencimiento:</td>
                                    <td class="text-right">{{ $subscription->ends_at->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Estado:</td>
                                    <td class="text-right">
                                        <span class="badge badge-success">Activa</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <p class="text-muted mb-4">
                        <i class="fas fa-envelope mr-1"></i>
                        Recibirás un email de confirmación con los detalles de tu compra.
                    </p>

                    <div class="d-flex justify-content-center">
                        <a href="{{ route('home') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-home mr-2"></i>
                            Ir al Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="text-muted small">
                    ¿Tienes preguntas sobre tu suscripción?
                    <br>
                    Contáctanos a <a href="mailto:soporte@rugbyhub.cl">soporte@rugbyhub.cl</a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes checkmark {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}
.fa-check-circle {
    animation: checkmark 0.5s ease-out;
}
</style>
@endsection

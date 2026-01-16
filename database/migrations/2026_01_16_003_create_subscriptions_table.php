<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('restrict');

            // Estado de la suscripción
            $table->enum('status', ['active', 'cancelled', 'expired', 'pending', 'trial'])->default('pending');

            // Proveedor de pago
            $table->enum('payment_provider', ['paypal', 'mercadopago'])->nullable();
            $table->string('provider_subscription_id')->nullable(); // ID en PayPal/MP

            // Período actual
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();

            // Cancelación
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            // Trial
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['organization_id', 'status']);
            $table->index('provider_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

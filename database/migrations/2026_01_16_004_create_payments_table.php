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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');

            // Proveedor de pago
            $table->enum('payment_provider', ['paypal', 'mercadopago']);
            $table->string('provider_payment_id')->nullable(); // ID de transacción en PayPal/MP

            // Monto y moneda
            $table->decimal('amount', 12, 2); // Monto total pagado
            $table->string('currency', 3); // CLP, USD, EUR, PEN, BRL
            $table->decimal('amount_usd', 12, 2)->nullable(); // Convertido a USD para reportes

            // Estado
            $table->enum('status', ['completed', 'pending', 'failed', 'refunded', 'partially_refunded'])->default('pending');

            // Datos adicionales del pago
            $table->string('payer_email')->nullable();
            $table->string('payer_name')->nullable();
            $table->string('country_code', 2)->nullable(); // País del pagador

            // Fechas
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Metadata del proveedor (JSON con respuesta completa)
            $table->json('provider_data')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['organization_id', 'status']);
            $table->index('provider_payment_id');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

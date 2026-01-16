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
        Schema::create('payment_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('partner_id')->constrained()->onDelete('restrict');

            // Monto que corresponde a este socio
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3); // Misma moneda del pago original

            // Porcentaje aplicado (para histórico, ya que el % puede cambiar)
            $table->decimal('percentage_applied', 5, 2);

            // Estado de la transferencia manual
            $table->enum('status', ['pending', 'transferred', 'failed'])->default('pending');
            $table->timestamp('transferred_at')->nullable();
            $table->text('notes')->nullable(); // Notas sobre la transferencia

            $table->timestamps();

            // Índices
            $table->index(['partner_id', 'status']);
            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_splits');
    }
};

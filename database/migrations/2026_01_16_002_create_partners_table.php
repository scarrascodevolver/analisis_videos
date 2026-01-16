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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del socio
            $table->string('email')->unique(); // Email de contacto
            $table->string('role')->default('partner'); // 'owner', 'partner'

            // Cuentas para recibir pagos
            $table->string('paypal_email')->nullable(); // Email PayPal
            $table->string('mercadopago_email')->nullable(); // Email Mercado Pago

            // Porcentaje del split (ej: 70.00 = 70%)
            $table->decimal('split_percentage', 5, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->boolean('can_edit_settings')->default(false); // Solo owner puede editar

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};

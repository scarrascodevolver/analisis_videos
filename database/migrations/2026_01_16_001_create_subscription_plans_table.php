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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Plan Mensual", "Plan Anual"
            $table->string('slug')->unique(); // "mensual", "anual"
            $table->text('description')->nullable();

            // Precios por región/moneda
            $table->decimal('price_clp', 10, 0)->default(0); // Chile (sin decimales)
            $table->decimal('price_pen', 10, 2)->default(0); // Perú
            $table->decimal('price_brl', 10, 2)->default(0); // Brasil
            $table->decimal('price_eur', 10, 2)->default(0); // Europa
            $table->decimal('price_usd', 10, 2)->default(0); // USD (resto del mundo)

            $table->integer('duration_months')->default(1); // 1, 6, 12
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable(); // Características incluidas

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};

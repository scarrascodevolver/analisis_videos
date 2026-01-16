<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class PaymentSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear socios iniciales
        $this->createPartners();

        // Crear planes de suscripción
        $this->createPlans();
    }

    private function createPartners(): void
    {
        // Owner - Desarrollador (puede editar todo)
        Partner::updateOrCreate(
            ['email' => 'eliascarrascoaguayo@gmail.com'],
            [
                'name' => 'Elias Carrasco',
                'role' => 'owner',
                'paypal_email' => 'eliascarrascoaguayo@gmail.com',
                'mercadopago_email' => 'eliascarrascoaguayo@gmail.com',
                'split_percentage' => 70.00,
                'is_active' => true,
                'can_edit_settings' => true,
            ]
        );

        // Partner - Analista (solo puede ver reportes)
        Partner::updateOrCreate(
            ['email' => 'jere@clublostroncos.cl'],
            [
                'name' => 'Jeremías Rodríguez',
                'role' => 'partner',
                'paypal_email' => null,
                'mercadopago_email' => null,
                'split_percentage' => 30.00,
                'is_active' => true,
                'can_edit_settings' => false,
            ]
        );

        $this->command->info('✅ Socios creados: Elias (70%), Jeremías (30%)');
    }

    private function createPlans(): void
    {
        // Plan Mensual
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'mensual'],
            [
                'name' => 'Plan Mensual',
                'description' => 'Acceso completo a todas las funcionalidades por 1 mes.',
                'price_clp' => 29990,    // ~$30 USD
                'price_pen' => 99.90,    // Perú
                'price_brl' => 149.90,   // Brasil
                'price_eur' => 25.00,    // Europa
                'price_usd' => 29.99,    // USD
                'duration_months' => 1,
                'is_active' => true,
                'features' => [
                    'Videos ilimitados',
                    'Análisis con anotaciones',
                    'Evaluaciones de jugadores',
                    'Jugadas tácticas',
                    'Soporte prioritario',
                ],
            ]
        );

        // Plan Semestral
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'semestral'],
            [
                'name' => 'Plan Semestral',
                'description' => 'Acceso completo por 6 meses con 20% de descuento.',
                'price_clp' => 143950,   // ~20% descuento
                'price_pen' => 479.40,
                'price_brl' => 719.50,
                'price_eur' => 120.00,
                'price_usd' => 143.95,
                'duration_months' => 6,
                'is_active' => true,
                'features' => [
                    'Todo del Plan Mensual',
                    '20% de descuento',
                    'Soporte prioritario',
                ],
            ]
        );

        // Plan Anual
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'anual'],
            [
                'name' => 'Plan Anual',
                'description' => 'Acceso completo por 12 meses con 30% de descuento.',
                'price_clp' => 251916,   // ~30% descuento
                'price_pen' => 839.16,
                'price_brl' => 1259.16,
                'price_eur' => 210.00,
                'price_usd' => 251.92,
                'duration_months' => 12,
                'is_active' => true,
                'features' => [
                    'Todo del Plan Mensual',
                    '30% de descuento',
                    'Soporte prioritario',
                    'Onboarding personalizado',
                ],
            ]
        );

        $this->command->info('✅ Planes creados: Mensual, Semestral, Anual');
    }
}

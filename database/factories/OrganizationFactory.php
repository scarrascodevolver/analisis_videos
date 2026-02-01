<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'logo_path' => null,
            'is_active' => true,
            'invitation_code' => strtoupper(\Illuminate\Support\Str::random(8)),
            'timezone' => 'UTC',
            'compression_strategy' => 'hybrid',
            'compression_start_hour' => 3,
            'compression_end_hour' => 7,
            'compression_hybrid_threshold' => 500,
        ];
    }
}

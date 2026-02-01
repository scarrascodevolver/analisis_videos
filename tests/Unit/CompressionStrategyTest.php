<?php

namespace Tests\Unit;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompressionStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_has_default_compression_settings(): void
    {
        $org = Organization::factory()->create();

        $this->assertEquals('UTC', $org->timezone);
        $this->assertEquals('hybrid', $org->compression_strategy);
        $this->assertEquals(3, $org->compression_start_hour);
        $this->assertEquals(7, $org->compression_end_hour);
        $this->assertEquals(500, $org->compression_hybrid_threshold);
    }

    public function test_organization_can_have_custom_timezone(): void
    {
        $org = Organization::factory()->create([
            'timezone' => 'America/Argentina/Buenos_Aires',
        ]);

        $this->assertEquals('America/Argentina/Buenos_Aires', $org->timezone);
    }

    public function test_organization_can_have_immediate_strategy(): void
    {
        $org = Organization::factory()->create([
            'compression_strategy' => 'immediate',
        ]);

        $this->assertEquals('immediate', $org->compression_strategy);
    }

    public function test_organization_can_have_nocturnal_strategy(): void
    {
        $org = Organization::factory()->create([
            'compression_strategy' => 'nocturnal',
        ]);

        $this->assertEquals('nocturnal', $org->compression_strategy);
    }

    public function test_organization_can_have_hybrid_strategy(): void
    {
        $org = Organization::factory()->create([
            'compression_strategy' => 'hybrid',
        ]);

        $this->assertEquals('hybrid', $org->compression_strategy);
    }

    public function test_organization_can_have_custom_time_window(): void
    {
        $org = Organization::factory()->create([
            'compression_start_hour' => 2,
            'compression_end_hour' => 8,
        ]);

        $this->assertEquals(2, $org->compression_start_hour);
        $this->assertEquals(8, $org->compression_end_hour);
    }

    public function test_organization_can_have_custom_hybrid_threshold(): void
    {
        $org = Organization::factory()->create([
            'compression_hybrid_threshold' => 1000,
        ]);

        $this->assertEquals(1000, $org->compression_hybrid_threshold);
    }

    public function test_compression_settings_validation_rules_exist(): void
    {
        $rules = Organization::compressionSettingsValidationRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('timezone', $rules);
        $this->assertArrayHasKey('compression_strategy', $rules);
        $this->assertArrayHasKey('compression_start_hour', $rules);
        $this->assertArrayHasKey('compression_end_hour', $rules);
        $this->assertArrayHasKey('compression_hybrid_threshold', $rules);
    }

    public function test_compression_settings_are_fillable(): void
    {
        $org = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'timezone' => 'Europe/Madrid',
            'compression_strategy' => 'nocturnal',
            'compression_start_hour' => 1,
            'compression_end_hour' => 6,
            'compression_hybrid_threshold' => 750,
        ]);

        $this->assertEquals('Europe/Madrid', $org->timezone);
        $this->assertEquals('nocturnal', $org->compression_strategy);
        $this->assertEquals(1, $org->compression_start_hour);
        $this->assertEquals(6, $org->compression_end_hour);
        $this->assertEquals(750, $org->compression_hybrid_threshold);
    }
}

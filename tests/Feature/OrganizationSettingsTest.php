<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Create super admin
        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'role' => 'analista',
        ]);

        // Create organization
        $this->organization = Organization::factory()->create([
            'name' => 'Test Rugby Club',
            'timezone' => 'UTC',
            'compression_strategy' => 'hybrid',
            'compression_start_hour' => 3,
            'compression_end_hour' => 7,
            'compression_hybrid_threshold' => 500,
        ]);

        $this->superAdmin->organizations()->attach($this->organization->id, [
            'role' => 'analista',
            'is_current' => true,
            'is_org_admin' => true,
        ]);
    }

    public function test_super_admin_can_access_organization_settings_page(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.organizations.settings', $this->organization));

        $response->assertStatus(200);
        $response->assertViewIs('super-admin.organizations.settings');
        $response->assertViewHas('organization');
        $response->assertViewHas('timezones');
    }

    public function test_regular_user_cannot_access_settings_page(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        $user->organizations()->attach($this->organization->id, [
            'role' => 'jugador',
            'is_current' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('super-admin.organizations.settings', $this->organization));

        $response->assertStatus(403);
    }

    public function test_can_update_timezone_setting(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('super-admin.organizations.settings.update', $this->organization), [
                'timezone' => 'America/Argentina/Buenos_Aires',
                'compression_strategy' => 'hybrid',
                'compression_start_hour' => 3,
                'compression_end_hour' => 7,
                'compression_hybrid_threshold' => 500,
            ]);

        $response->assertRedirect(route('super-admin.organizations.settings', $this->organization));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
            'timezone' => 'America/Argentina/Buenos_Aires',
        ]);
    }

    public function test_can_update_compression_strategy_to_immediate(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('super-admin.organizations.settings.update', $this->organization), [
                'timezone' => 'UTC',
                'compression_strategy' => 'immediate',
                'compression_start_hour' => 3,
                'compression_end_hour' => 7,
                'compression_hybrid_threshold' => 500,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
            'compression_strategy' => 'immediate',
        ]);
    }

    public function test_can_update_compression_strategy_to_nocturnal(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('super-admin.organizations.settings.update', $this->organization), [
                'timezone' => 'UTC',
                'compression_strategy' => 'nocturnal',
                'compression_start_hour' => 2,
                'compression_end_hour' => 8,
                'compression_hybrid_threshold' => 500,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
            'compression_strategy' => 'nocturnal',
            'compression_start_hour' => 2,
            'compression_end_hour' => 8,
        ]);
    }

    public function test_can_update_hybrid_threshold(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('super-admin.organizations.settings.update', $this->organization), [
                'timezone' => 'UTC',
                'compression_strategy' => 'hybrid',
                'compression_start_hour' => 3,
                'compression_end_hour' => 7,
                'compression_hybrid_threshold' => 1000,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
            'compression_hybrid_threshold' => 1000,
        ]);
    }

    public function test_validation_fails_for_invalid_timezone(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('super-admin.organizations.settings.update', $this->organization), [
                'timezone' => 'Invalid/Timezone',
                'compression_strategy' => 'hybrid',
                'compression_start_hour' => 3,
                'compression_end_hour' => 7,
                'compression_hybrid_threshold' => 500,
            ]);

        $response->assertSessionHasErrors('timezone');
    }

    public function test_validation_fails_when_end_hour_is_less_than_start_hour(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('super-admin.organizations.settings.update', $this->organization), [
                'timezone' => 'UTC',
                'compression_strategy' => 'nocturnal',
                'compression_start_hour' => 7,
                'compression_end_hour' => 3,
                'compression_hybrid_threshold' => 500,
            ]);

        $response->assertSessionHasErrors('compression_end_hour');
    }

    public function test_validation_fails_for_hour_out_of_range(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('super-admin.organizations.settings.update', $this->organization), [
                'timezone' => 'UTC',
                'compression_strategy' => 'nocturnal',
                'compression_start_hour' => 25,
                'compression_end_hour' => 30,
                'compression_hybrid_threshold' => 500,
            ]);

        $response->assertSessionHasErrors(['compression_start_hour', 'compression_end_hour']);
    }

    public function test_validation_fails_for_threshold_below_minimum(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('super-admin.organizations.settings.update', $this->organization), [
                'timezone' => 'UTC',
                'compression_strategy' => 'hybrid',
                'compression_start_hour' => 3,
                'compression_end_hour' => 7,
                'compression_hybrid_threshold' => 50,
            ]);

        $response->assertSessionHasErrors('compression_hybrid_threshold');
    }

    public function test_validation_fails_for_threshold_above_maximum(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('super-admin.organizations.settings.update', $this->organization), [
                'timezone' => 'UTC',
                'compression_strategy' => 'hybrid',
                'compression_start_hour' => 3,
                'compression_end_hour' => 7,
                'compression_hybrid_threshold' => 15000,
            ]);

        $response->assertSessionHasErrors('compression_hybrid_threshold');
    }

    public function test_validation_fails_for_invalid_compression_strategy(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('super-admin.organizations.settings.update', $this->organization), [
                'timezone' => 'UTC',
                'compression_strategy' => 'invalid_strategy',
                'compression_start_hour' => 3,
                'compression_end_hour' => 7,
                'compression_hybrid_threshold' => 500,
            ]);

        $response->assertSessionHasErrors('compression_strategy');
    }
}

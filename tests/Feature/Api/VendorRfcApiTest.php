<?php

namespace Tests\Feature\Api;

use App\Models\Organization;
use Database\Factories\OrganizationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorRfcApiTest extends TestCase
{
    use RefreshDatabase;

    private function bearerFor(Organization $organization): string
    {
        return "{$organization->id}|".OrganizationFactory::plainToken();
    }

    public function test_show_returns_null_rfc_when_nothing_saved_yet(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->bearerFor($organization))
            ->getJson('/api/v1/vendors/vendor-1/rfc');

        $response->assertOk()->assertJson([
            'zoho_vendor_id' => 'vendor-1',
            'rfc' => null,
        ]);
    }

    public function test_store_saves_a_valid_rfc_and_show_then_returns_it(): void
    {
        $organization = Organization::factory()->create();
        $bearer = 'Bearer '.$this->bearerFor($organization);

        $this->withHeader('Authorization', $bearer)
            ->putJson('/api/v1/vendors/vendor-1/rfc?rfc=aaa010101aaa')
            ->assertOk()
            ->assertJson(['zoho_vendor_id' => 'vendor-1', 'rfc' => 'AAA010101AAA']);

        $this->withHeader('Authorization', $bearer)
            ->getJson('/api/v1/vendors/vendor-1/rfc')
            ->assertOk()
            ->assertJson(['zoho_vendor_id' => 'vendor-1', 'rfc' => 'AAA010101AAA']);

        $this->assertDatabaseHas('vendor_rfc_overrides', [
            'organization_id' => $organization->id,
            'zoho_vendor_id' => 'vendor-1',
            'rfc_normalized' => 'AAA010101AAA',
        ]);
    }

    public function test_store_rejects_a_structurally_invalid_rfc(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->bearerFor($organization))
            ->putJson('/api/v1/vendors/vendor-1/rfc?rfc=no-es-un-rfc');

        $response->assertStatus(422)->assertJson(['error' => 'rfc_invalido']);

        $this->assertDatabaseMissing('vendor_rfc_overrides', ['zoho_vendor_id' => 'vendor-1']);
    }

    public function test_saving_again_updates_the_existing_override_instead_of_duplicating(): void
    {
        $organization = Organization::factory()->create();
        $bearer = 'Bearer '.$this->bearerFor($organization);

        $this->withHeader('Authorization', $bearer)->putJson('/api/v1/vendors/vendor-1/rfc?rfc=AAA010101AAA')->assertOk();
        $this->withHeader('Authorization', $bearer)->putJson('/api/v1/vendors/vendor-1/rfc?rfc=BBB020202BB1')->assertOk();

        $this->assertDatabaseCount('vendor_rfc_overrides', 1);
        $this->assertDatabaseHas('vendor_rfc_overrides', [
            'organization_id' => $organization->id,
            'zoho_vendor_id' => 'vendor-1',
            'rfc_normalized' => 'BBB020202BB1',
        ]);
    }

    public function test_vendor_rfc_overrides_are_isolated_per_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$this->bearerFor($orgA))
            ->putJson('/api/v1/vendors/vendor-1/rfc?rfc=AAA010101AAA')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$this->bearerFor($orgB))
            ->getJson('/api/v1/vendors/vendor-1/rfc')
            ->assertOk()
            ->assertJson(['rfc' => null]);
    }

    public function test_vendor_rfc_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/vendors/vendor-1/rfc')->assertStatus(401);
        $this->putJson('/api/v1/vendors/vendor-1/rfc?rfc=AAA010101AAA')->assertStatus(401);
    }
}

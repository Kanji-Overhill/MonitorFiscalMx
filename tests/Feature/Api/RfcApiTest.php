<?php

namespace Tests\Feature\Api;

use App\Models\Organization;
use App\Models\SatDataset;
use App\Models\SatRecord;
use Database\Factories\OrganizationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfcApiTest extends TestCase
{
    use RefreshDatabase;

    private function bearerFor(Organization $organization): string
    {
        return "{$organization->id}|".OrganizationFactory::plainToken();
    }

    public function test_health_endpoint_is_public_and_reports_dataset_state(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_status_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/rfcs/AAA010101AAA/status');

        $response->assertStatus(401);
    }

    public function test_status_endpoint_rejects_an_invalid_token(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$organization->id}|wrong-token")
            ->getJson('/api/v1/rfcs/AAA010101AAA/status');

        $response->assertStatus(401);
    }

    public function test_status_endpoint_returns_the_stable_json_contract_and_records_a_check(): void
    {
        $organization = Organization::factory()->create();
        $dataset = SatDataset::factory()->active()->create();
        SatRecord::factory()->for($dataset, 'dataset')->create([
            'rfc_normalized' => 'AAA010101AAA',
            'classification' => 'definitivo',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->bearerFor($organization))
            ->getJson('/api/v1/rfcs/AAA010101AAA/status?zoho_vendor_id=vendor-1');

        $response->assertOk()->assertJson([
            'rfc' => 'AAA010101AAA',
            'valid' => true,
            'matched' => true,
            'status' => 'definitivo',
        ]);

        $this->assertDatabaseHas('vendor_checks', [
            'organization_id' => $organization->id,
            'zoho_vendor_id' => 'vendor-1',
            'rfc_normalized' => 'AAA010101AAA',
            'result_status' => 'definitivo',
        ]);
    }

    public function test_history_is_isolated_per_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$this->bearerFor($orgA))
            ->getJson('/api/v1/rfcs/AAA010101AAA/status')
            ->assertOk();

        $responseForOrgB = $this->withHeader('Authorization', 'Bearer '.$this->bearerFor($orgB))
            ->getJson('/api/v1/rfcs/AAA010101AAA/history');

        $responseForOrgB->assertOk()->assertJsonCount(0, 'history');

        $responseForOrgA = $this->withHeader('Authorization', 'Bearer '.$this->bearerFor($orgA))
            ->getJson('/api/v1/rfcs/AAA010101AAA/history');

        $responseForOrgA->assertOk()->assertJsonCount(1, 'history');
    }
}

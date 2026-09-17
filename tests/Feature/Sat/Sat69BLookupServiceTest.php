<?php

namespace Tests\Feature\Sat;

use App\Models\SatDataset;
use App\Models\SatRecord;
use App\Services\Sat\Sat69BLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sat69BLookupServiceTest extends TestCase
{
    use RefreshDatabase;

    private Sat69BLookupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(Sat69BLookupService::class);
    }

    public function test_no_active_dataset_returns_fuente_no_disponible(): void
    {
        $result = $this->service->lookup('AAA010101AAA');

        $this->assertSame('fuente_no_disponible', $result['status']);
        $this->assertFalse($result['matched']);
        $this->assertTrue($result['valid']);
    }

    public function test_structurally_invalid_rfc_returns_rfc_invalido_without_querying_dataset(): void
    {
        $result = $this->service->lookup('no-es-un-rfc');

        $this->assertSame('rfc_invalido', $result['status']);
        $this->assertFalse($result['valid']);
        $this->assertFalse($result['matched']);
    }

    public function test_rfc_without_a_match_returns_sin_coincidencia(): void
    {
        SatDataset::factory()->active()->create();

        $result = $this->service->lookup('ZZZ010101ZZ9');

        $this->assertSame('sin_coincidencia', $result['status']);
        $this->assertFalse($result['matched']);
        $this->assertSame([], $result['matches']);
    }

    public function test_a_presunto_match_is_reported_correctly(): void
    {
        $dataset = SatDataset::factory()->active()->create();
        SatRecord::factory()->for($dataset, 'dataset')->create([
            'rfc_normalized' => 'AAA010101AAA',
            'classification' => 'presunto',
        ]);

        $result = $this->service->lookup('AAA010101AAA');

        $this->assertSame('presunto', $result['status']);
        $this->assertTrue($result['matched']);
        $this->assertCount(1, $result['matches']);
    }

    public function test_a_definitivo_match_is_reported_correctly(): void
    {
        $dataset = SatDataset::factory()->active()->create();
        SatRecord::factory()->for($dataset, 'dataset')->create([
            'rfc_normalized' => 'AAA010101AAA',
            'classification' => 'definitivo',
        ]);

        $result = $this->service->lookup('AAA010101AAA');

        $this->assertSame('definitivo', $result['status']);
        $this->assertTrue($result['matched']);
    }

    public function test_a_desvirtuado_match_is_reported_correctly(): void
    {
        $dataset = SatDataset::factory()->active()->create();
        SatRecord::factory()->for($dataset, 'dataset')->create([
            'rfc_normalized' => 'AAA010101AAA',
            'classification' => 'desvirtuado',
        ]);

        $result = $this->service->lookup('AAA010101AAA');

        $this->assertSame('desvirtuado', $result['status']);
        $this->assertTrue($result['matched']);
    }

    public function test_multiple_matches_for_the_same_rfc_are_all_returned(): void
    {
        $dataset = SatDataset::factory()->active()->create();

        SatRecord::factory()->for($dataset, 'dataset')->create([
            'rfc_normalized' => 'AAA010101AAA',
            'classification' => 'presunto',
            'publication_date' => '2026-01-01',
        ]);

        SatRecord::factory()->for($dataset, 'dataset')->create([
            'rfc_normalized' => 'AAA010101AAA',
            'classification' => 'definitivo',
            'publication_date' => '2026-06-01',
        ]);

        $result = $this->service->lookup('AAA010101AAA');

        $this->assertTrue($result['matched']);
        $this->assertCount(2, $result['matches']);
        // Most recent publication_date is surfaced as the top-level status.
        $this->assertSame('definitivo', $result['status']);
    }
}

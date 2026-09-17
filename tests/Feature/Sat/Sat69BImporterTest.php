<?php

namespace Tests\Feature\Sat;

use App\Enums\DatasetStatus;
use App\Models\SatDataset;
use App\Models\SatRecord;
use App\Services\Sat\Exceptions\SatImportException;
use App\Services\Sat\Exceptions\SatSourceUnavailableException;
use App\Services\Sat\Sat69BImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Sat69BImporterTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $name): string
    {
        return base_path("database/fixtures/sat69b/{$name}");
    }

    public function test_it_imports_a_valid_fixture(): void
    {
        $dataset = $this->app->make(Sat69BImporter::class)
            ->importFromLocalFile($this->fixture('sat69b_valid.csv'));

        $this->assertSame(DatasetStatus::Active, $dataset->status);
        $this->assertSame(4, $dataset->record_count);
        $this->assertDatabaseCount('sat_records', 4);
        $this->assertSame(
            'definitivo',
            SatRecord::where('rfc_normalized', 'BBB020202BB1')->first()->classification,
        );
    }

    public function test_reimporting_the_same_file_is_treated_as_a_duplicate(): void
    {
        $importer = $this->app->make(Sat69BImporter::class);

        $first = $importer->importFromLocalFile($this->fixture('sat69b_valid.csv'));
        $second = $importer->importFromLocalFile($this->fixture('sat69b_valid.csv'));

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, SatDataset::count());
    }

    public function test_a_corrupt_file_fails_without_touching_the_previous_active_dataset(): void
    {
        $importer = $this->app->make(Sat69BImporter::class);

        $active = $importer->importFromLocalFile($this->fixture('sat69b_valid.csv'));

        $this->expectException(SatImportException::class);

        try {
            $importer->importFromLocalFile($this->fixture('sat69b_corrupt.csv'));
        } finally {
            $this->assertSame(DatasetStatus::Active, $active->fresh()->status);
        }
    }

    public function test_a_file_with_unexpected_columns_fails_with_a_descriptive_error(): void
    {
        $importer = $this->app->make(Sat69BImporter::class);

        try {
            $importer->importFromLocalFile($this->fixture('sat69b_unexpected_columns.csv'));
            $this->fail('Expected SatImportException was not thrown.');
        } catch (SatImportException $exception) {
            $this->assertStringContainsString('RFC', $exception->getMessage());
        }

        $dataset = SatDataset::first();
        $this->assertSame(DatasetStatus::Failed, $dataset->status);
        $this->assertNotNull($dataset->error_message);
    }

    public function test_a_newer_dataset_supersedes_the_previous_active_one(): void
    {
        $importer = $this->app->make(Sat69BImporter::class);

        $first = $importer->importFromLocalFile($this->fixture('sat69b_valid.csv'));
        $second = $importer->importFromLocalFile($this->fixture('sat69b_updated.csv'));

        $this->assertSame(DatasetStatus::Superseded, $first->fresh()->status);
        $this->assertSame(DatasetStatus::Active, $second->fresh()->status);
    }

    public function test_sat_source_unavailable_throws_a_dedicated_exception(): void
    {
        Http::fake([
            '*' => Http::response('', 503),
        ]);

        $this->expectException(SatSourceUnavailableException::class);

        $this->app->make(Sat69BImporter::class)->importFromUrl('https://example.test/listado.csv');
    }

    public function test_it_imports_the_real_sat_format_with_disclaimer_rows_and_per_stage_columns(): void
    {
        $dataset = $this->app->make(Sat69BImporter::class)
            ->importFromLocalFile($this->fixture('sat69b_real_format.csv'));

        $this->assertSame(DatasetStatus::Active, $dataset->status);
        $this->assertSame(4, $dataset->record_count);

        $presunto = SatRecord::where('rfc_normalized', 'AAA010101AAA')->first();
        $this->assertSame('presunto', $presunto->classification);
        $this->assertSame('2026-01-01', $presunto->publication_date->toDateString());
        $this->assertStringContainsString('500-05-2026-00001', $presunto->official_document);

        $desvirtuado = SatRecord::where('rfc_normalized', 'BBB020202BB1')->first();
        $this->assertSame('desvirtuado', $desvirtuado->classification);
        // Debe tomar el oficio/fecha de "desvirtuado", no el de "presunto" que también trae la fila.
        $this->assertSame('2026-03-03', $desvirtuado->publication_date->toDateString());
        $this->assertStringContainsString('500-05-2026-00003', $desvirtuado->official_document);

        $definitivo = SatRecord::where('rfc_normalized', 'CCC030303CC2')->first();
        $this->assertSame('2026-05-05', $definitivo->publication_date->toDateString());

        $sentenciaFavorable = SatRecord::where('rfc_normalized', 'DDD040404DD3')->first();
        $this->assertSame('2026-07-07', $sentenciaFavorable->publication_date->toDateString());
    }
}

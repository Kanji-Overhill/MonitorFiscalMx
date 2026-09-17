<?php

namespace App\Services\Sat;

use App\Enums\DatasetStatus;
use App\Models\SatDataset;
use App\Models\SatRecord;
use App\Services\Sat\Exceptions\SatImportException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class Sat69BImporter
{
    private const DATASET_TYPE = '69b';

    private const INSERT_CHUNK_SIZE = 500;

    public function __construct(
        private readonly SatSourceFetcher $fetcher,
        private readonly CsvParser $csvParser,
        private readonly HeaderMapper $headerMapper,
        private readonly ClassificationMapper $classificationMapper,
        private readonly RfcNormalizer $rfcNormalizer,
        private readonly RfcValidator $rfcValidator,
    ) {}

    public function importFromUrl(string $url, int $timeoutSeconds = 60): SatDataset
    {
        $content = $this->fetcher->fetch($url, $timeoutSeconds);

        return $this->importContent($content, $url, basename(parse_url($url, PHP_URL_PATH) ?: 'listado_69b'));
    }

    public function importFromLocalFile(string $path): SatDataset
    {
        if (! is_readable($path)) {
            throw new SatImportException("No se pudo leer el archivo local: {$path}");
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new SatImportException("No se pudo leer el archivo local: {$path}");
        }

        return $this->importContent($content, "file://{$path}", basename($path));
    }

    private function importContent(string $content, string $sourceUrl, string $filename): SatDataset
    {
        $checksum = hash('sha256', $content);

        $existing = SatDataset::query()
            ->where('type', self::DATASET_TYPE)
            ->where('checksum', $checksum)
            ->whereIn('status', [DatasetStatus::Active, DatasetStatus::Processing])
            ->first();

        if ($existing) {
            Log::info('sat.import.duplicate_skipped', ['dataset_id' => $existing->id, 'checksum' => $checksum]);

            return $existing;
        }

        $dataset = SatDataset::create([
            'type' => self::DATASET_TYPE,
            'source_url' => $sourceUrl,
            'source_filename' => $filename,
            'downloaded_at' => now(),
            'checksum' => $checksum,
            'status' => DatasetStatus::Processing,
        ]);

        try {
            [$recordCount, $skipped] = $this->parseAndPersist($content, $dataset);

            DB::transaction(function () use ($dataset) {
                SatDataset::query()
                    ->where('type', self::DATASET_TYPE)
                    ->where('id', '!=', $dataset->id)
                    ->where('status', DatasetStatus::Active)
                    ->update(['status' => DatasetStatus::Superseded]);
            });

            $dataset->update([
                'status' => DatasetStatus::Active,
                'record_count' => $recordCount,
                'source_updated_at' => now()->toDateString(),
                'error_message' => $skipped > 0
                    ? "{$skipped} fila(s) omitida(s) por RFC inválido o clasificación no reconocida."
                    : null,
            ]);
        } catch (Throwable $exception) {
            // Una QueryException de un insert masivo incluye el SQL y todos
            // los bindings en el mensaje, que puede pesar varios cientos de
            // KB para un chunk de cientos de filas. Se guarda un resumen
            // acotado; el detalle completo va solo al log.
            $summary = $this->summarizeException($exception);

            $dataset->update([
                'status' => DatasetStatus::Failed,
                'error_message' => $summary,
            ]);

            Log::error('sat.import.failed', [
                'dataset_id' => $dataset->id,
                'error' => $summary,
            ]);

            throw $exception instanceof SatImportException
                ? $exception
                : new SatImportException("Error al importar el listado 69-B: {$summary}", previous: $exception);
        }

        return $dataset->fresh();
    }

    /**
     * @return array{0: int, 1: int} [insertedRecordCount, skippedRowCount]
     */
    private function parseAndPersist(string $content, SatDataset $dataset): array
    {
        $rows = $this->csvParser->parse($content);

        // El SAT publica el archivo con filas de aviso legal/título antes
        // del encabezado real; se busca la fila que realmente contiene "RFC"
        // en lugar de asumir que es la primera.
        $located = $this->headerMapper->locateHeaderRow($rows);

        if ($located === null) {
            throw new SatImportException('No se encontró una columna de RFC en el archivo del SAT.');
        }

        $header = $located['header'];
        $rows = $located['dataRows'];
        $columns = $this->headerMapper->map($header);

        if (! isset($columns['rfc'])) {
            throw new SatImportException('No se encontró una columna de RFC en el archivo del SAT.');
        }

        if (! isset($columns['classification'])) {
            throw new SatImportException('No se encontró una columna de situación/clasificación del contribuyente en el archivo del SAT.');
        }

        $toInsert = [];
        $skipped = 0;
        $now = now();

        foreach ($rows as $row) {
            $rfc = $this->rfcNormalizer->normalize($row[$columns['rfc']] ?? null);

            if ($rfc === '' || ! $this->rfcValidator->isStructurallyValid($rfc)) {
                $skipped++;

                continue;
            }

            $classification = $this->classificationMapper->map((string) ($row[$columns['classification']] ?? ''));

            if ($classification === null) {
                $skipped++;

                continue;
            }

            [$officialDocument, $publicationDate] = $this->resolveStageColumns($row, $columns, $classification);

            $toInsert[] = [
                'dataset_id' => $dataset->id,
                'rfc_normalized' => $rfc,
                'business_name' => isset($columns['business_name']) ? $this->nullableCell($row, $columns['business_name']) : null,
                'classification' => $classification,
                'official_document' => $officialDocument,
                'publication_date' => $publicationDate,
                'raw_data' => json_encode($this->buildRawRow($header, $row), JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($toInsert === []) {
            throw new SatImportException('El archivo no contiene ningún registro válido tras la normalización.');
        }

        foreach (array_chunk($toInsert, self::INSERT_CHUNK_SIZE) as $chunk) {
            SatRecord::insert($chunk);
        }

        return [count($toInsert), $skipped];
    }

    private function summarizeException(Throwable $exception): string
    {
        $message = str(get_class($exception).': '.$exception->getMessage())->limit(1000);

        return (string) $message;
    }

    /**
     * The real "Listado completo" file has one oficio/fecha de publicación
     * column pair per classification stage instead of a single generic
     * pair (see HeaderMapper). Prefers the pair matching this row's
     * classification, falling back to the generic columns for simpler
     * single-column files.
     *
     * @param  string[]  $row
     * @param  array<string, int>  $columns
     * @return array{0: ?string, 1: ?string} [officialDocument, publicationDate]
     */
    private function resolveStageColumns(array $row, array $columns, string $classification): array
    {
        $oficioKey = "oficio_{$classification}";
        $fechaKey = "fecha_{$classification}";

        $officialDocument = isset($columns[$oficioKey])
            ? $this->nullableCell($row, $columns[$oficioKey])
            : (isset($columns['official_document']) ? $this->nullableCell($row, $columns['official_document']) : null);

        $publicationDate = isset($columns[$fechaKey])
            ? $this->parseDate($this->nullableCell($row, $columns[$fechaKey]))
            : (isset($columns['publication_date']) ? $this->parseDate($this->nullableCell($row, $columns['publication_date'])) : null);

        return [$officialDocument, $publicationDate];
    }

    /**
     * @param  string[]  $row
     */
    private function nullableCell(array $row, int $index): ?string
    {
        $value = $row[$index] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function parseDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param  string[]  $header
     * @param  string[]  $row
     * @return array<string, string|null>
     */
    private function buildRawRow(array $header, array $row): array
    {
        $raw = [];

        foreach ($header as $index => $label) {
            $raw[$label] = $row[$index] ?? null;
        }

        return $raw;
    }
}

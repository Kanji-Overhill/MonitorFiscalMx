<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Sat\Exceptions\SatImportException;
use App\Services\Sat\Exceptions\SatSourceUnavailableException;
use App\Services\Sat\Sat69BImporter;
use Illuminate\Http\JsonResponse;

/**
 * Optional, disabled-by-default HTTP trigger for the 69-B import.
 * Prefer `php artisan sat:import-69b` (see AuthenticateSatImportEndpoint).
 */
class SatDatasetImportController extends Controller
{
    public function __invoke(Sat69BImporter $importer): JsonResponse
    {
        $sourceUrl = config('sat.source_url');

        if (! $sourceUrl) {
            return response()->json(['message' => 'SAT_69B_SOURCE_URL no está configurada.'], 422);
        }

        try {
            $dataset = $importer->importFromUrl($sourceUrl, (int) config('sat.download_timeout'));
        } catch (SatSourceUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 502);
        } catch (SatImportException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'dataset_id' => $dataset->id,
            'status' => $dataset->status->value,
            'record_count' => $dataset->record_count,
            'error_message' => $dataset->error_message,
        ]);
    }
}

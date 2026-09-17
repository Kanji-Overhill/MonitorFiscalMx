<?php

namespace App\Services\Sat;

use App\Services\Sat\Exceptions\SatSourceUnavailableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SatSourceFetcher
{
    /**
     * Downloads the raw file content from an HTTP(S) URL.
     *
     * @throws SatSourceUnavailableException
     */
    public function fetch(string $url, int $timeoutSeconds = 60): string
    {
        try {
            $response = Http::timeout($timeoutSeconds)->get($url);
        } catch (Throwable $exception) {
            Log::warning('sat.import.fetch_failed', ['url' => $url, 'error' => $exception->getMessage()]);

            throw new SatSourceUnavailableException(
                "No se pudo contactar la fuente del SAT ({$url}).",
                previous: $exception,
            );
        }

        if ($response->failed()) {
            Log::warning('sat.import.fetch_non_2xx', ['url' => $url, 'status' => $response->status()]);

            throw new SatSourceUnavailableException(
                "La fuente del SAT respondió con estado HTTP {$response->status()}.",
            );
        }

        $body = $response->body();

        if (trim($body) === '') {
            throw new SatSourceUnavailableException('La fuente del SAT devolvió una respuesta vacía.');
        }

        return $body;
    }
}

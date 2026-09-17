<?php

namespace App\Services\Sat;

use App\Services\Sat\Exceptions\SatImportException;

class CsvParser
{
    /**
     * Parses raw CSV content into a list of rows (each row a list of cells).
     * Strips a UTF-8 BOM if present and auto-detects comma vs semicolon delimiters.
     *
     * @return list<list<string>>
     */
    public function parse(string $content): array
    {
        $content = $this->normalizeEncoding($content);
        $content = $this->stripBom($content);

        $handle = fopen('php://temp', 'r+b');

        if ($handle === false) {
            throw new SatImportException('No se pudo inicializar el parser de CSV.');
        }

        fwrite($handle, $content);
        rewind($handle);

        $delimiter = $this->detectDelimiter($content);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($row === [null] || $row === ['']) {
                continue;
            }

            $rows[] = array_map(static fn ($cell) => is_string($cell) ? trim($cell) : $cell, $row);
        }

        fclose($handle);

        if ($rows === []) {
            throw new SatImportException('El archivo no contiene filas interpretables como CSV.');
        }

        return $rows;
    }

    /**
     * The SAT has published this file in Windows-1252/ISO-8859-1 in the
     * past (accented business names get mangled otherwise). Converts to
     * UTF-8 when the content isn't already valid UTF-8.
     */
    private function normalizeEncoding(string $content): string
    {
        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        $detected = mb_detect_encoding($content, ['Windows-1252', 'ISO-8859-1'], true) ?: 'Windows-1252';

        return mb_convert_encoding($content, 'UTF-8', $detected);
    }

    private function stripBom(string $content): string
    {
        return str_starts_with($content, "\xEF\xBB\xBF")
            ? substr($content, 3)
            : $content;
    }

    private function detectDelimiter(string $content): string
    {
        $firstLine = strtok($content, "\n") ?: '';

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }
}

<?php

namespace App\Services\Sat;

/**
 * Resolves the SAT's CSV column headers to canonical field keys.
 *
 * The SAT has changed column names and ordering across publications of the
 * 69-B list, so we match on normalized (accent/case/space-insensitive)
 * substrings instead of assuming a fixed header row.
 *
 * The real "Listado completo" file publishes a *separate* oficio/fecha de
 * publicación pair per classification stage (presunto/desvirtuado/
 * definitivo/sentencia favorable) instead of a single generic pair, so both
 * are mapped here: the stage-specific keys (`oficio_<clasificacion>`,
 * `fecha_<clasificacion>`) are preferred by the importer when present, with
 * the generic `official_document`/`publication_date` kept as a fallback for
 * simpler single-column files (e.g. our test fixtures).
 */
class HeaderMapper
{
    /**
     * Order matters: a header cell is assigned to the first field (in this
     * order) whose needle matches, so the more specific per-stage needles
     * must come before the generic `official_document`/`publication_date`
     * fallback — otherwise the generic needle ("oficio global") would steal
     * every stage-specific oficio column before its specific needle gets a
     * chance to match.
     */
    private const CANONICAL_NEEDLES = [
        'rfc' => ['rfc'],
        'business_name' => ['nombre del contribuyente', 'nombre contribuyente', 'nombre'],
        'classification' => ['situacion del contribuyente', 'situacion contribuyente', 'situacion'],
        'oficio_presunto' => ['oficio global de presuncion sat'],
        'fecha_presunto' => ['publicacion pagina sat presuntos'],
        'oficio_desvirtuado' => ['desvirtuaron sat'],
        'fecha_desvirtuado' => ['publicacion pagina sat desvirtuados'],
        'oficio_definitivo' => ['oficio global de definitivos sat'],
        'fecha_definitivo' => ['publicacion pagina sat definitivos'],
        'oficio_sentencia_favorable' => ['oficio global de sentencia favorable sat'],
        'fecha_sentencia_favorable' => ['publicacion pagina sat sentencia favorable'],
        'official_document' => ['numero y fecha de oficio', 'oficio global', 'numero de oficio'],
        'publication_date' => ['fecha de publicacion', 'fecha publicacion dof', 'publicacion en el dof'],
    ];

    /**
     * @param  string[]  $headerRow
     * @return array<string, int> canonical field key => column index
     */
    public function map(array $headerRow): array
    {
        $resolved = [];

        foreach ($headerRow as $index => $rawHeader) {
            $normalized = $this->normalize($rawHeader);

            foreach (self::CANONICAL_NEEDLES as $field => $needles) {
                if (isset($resolved[$field])) {
                    continue;
                }

                foreach ($needles as $needle) {
                    if (str_contains($normalized, $needle)) {
                        $resolved[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        return $resolved;
    }

    /**
     * Locates the real header row within the first rows of a parsed CSV,
     * tolerating leading disclaimer/title rows (as published by the SAT).
     * Returns null if no row within the scan window has a cell that is
     * exactly "RFC" once normalized.
     *
     * @param  list<list<string>>  $rows
     * @return array{header: list<string>, dataRows: list<list<string>>}|null
     */
    public function locateHeaderRow(array $rows, int $scanWindow = 10): ?array
    {
        $limit = min($scanWindow, count($rows));

        for ($i = 0; $i < $limit; $i++) {
            foreach ($rows[$i] as $cell) {
                if ($this->normalize((string) $cell) === 'rfc') {
                    return [
                        'header' => $rows[$i],
                        'dataRows' => array_slice($rows, $i + 1),
                    ];
                }
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]);

        return preg_replace('/[^a-z ]/', '', $value) ?? $value;
    }
}

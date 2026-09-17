<?php

namespace App\Services\Sat;

use App\Enums\FiscalStatus;

/**
 * Maps the free-text "situación del contribuyente" published by the SAT to
 * our internal classification values. Matching is done on a normalized
 * (accent-stripped, lowercase) string because the SAT has used slightly
 * different wording across publications.
 */
class ClassificationMapper
{
    /** @var array<string, string> */
    private const MAP = [
        'presunto' => FiscalStatus::Presunto->value,
        'presuntos' => FiscalStatus::Presunto->value,
        'definitivo' => FiscalStatus::Definitivo->value,
        'definitivos' => FiscalStatus::Definitivo->value,
        'desvirtuado' => FiscalStatus::Desvirtuado->value,
        'desvirtuados' => FiscalStatus::Desvirtuado->value,
        'sentenciafavorable' => FiscalStatus::SentenciaFavorable->value,
        'sentenciafavorablescontribuyentesfavorecidos' => FiscalStatus::SentenciaFavorable->value,
    ];

    /**
     * Returns null when the raw value cannot be confidently mapped, so the
     * caller can record it as an interpretation error instead of guessing.
     */
    public function map(string $rawValue): ?string
    {
        $normalized = $this->normalize($rawValue);

        foreach (self::MAP as $needle => $classification) {
            if (str_contains($normalized, $needle)) {
                return $classification;
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

        return preg_replace('/[^a-z]/', '', $value) ?? $value;
    }
}

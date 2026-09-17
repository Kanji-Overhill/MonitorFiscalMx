<?php

namespace App\Services\Sat;

/**
 * Structural validation only (length, character classes, date-like segment).
 *
 * No homoclave/check-digit algorithm is implemented: there is no sufficiently
 * clear official SAT specification for it that we could confirm and cite for
 * this MVP, so we deliberately stop at structural validation as instructed.
 * See docs/zoho-open-questions.md.
 */
class RfcValidator
{
    private const MORAL_PATTERN = '/^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/u';

    private const FISICA_PATTERN = '/^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/u';

    public function __construct(
        private readonly RfcNormalizer $normalizer = new RfcNormalizer,
    ) {}

    public function isStructurallyValid(string $normalizedRfc): bool
    {
        if (preg_match(self::FISICA_PATTERN, $normalizedRfc) === 1) {
            return $this->hasPlausibleDate($normalizedRfc, 4);
        }

        if (preg_match(self::MORAL_PATTERN, $normalizedRfc) === 1) {
            return $this->hasPlausibleDate($normalizedRfc, 3);
        }

        return false;
    }

    public function isPersonaFisica(string $normalizedRfc): bool
    {
        return strlen($normalizedRfc) === 13 && preg_match(self::FISICA_PATTERN, $normalizedRfc) === 1;
    }

    public function isPersonaMoral(string $normalizedRfc): bool
    {
        return strlen($normalizedRfc) === 12 && preg_match(self::MORAL_PATTERN, $normalizedRfc) === 1;
    }

    private function hasPlausibleDate(string $rfc, int $letterCount): bool
    {
        $datePart = substr($rfc, $letterCount, 6);

        if (! ctype_digit($datePart)) {
            return false;
        }

        $month = (int) substr($datePart, 2, 2);
        $day = (int) substr($datePart, 4, 2);

        return $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31;
    }
}

<?php

namespace App\Services\Sat;

class RfcNormalizer
{
    /**
     * Uppercase, strip whitespace/invisible characters and non-RFC characters.
     * Does not validate structure — see RfcValidator for that.
     */
    public function normalize(?string $rfc): string
    {
        if ($rfc === null) {
            return '';
        }

        $rfc = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00A0}]/u', '', $rfc) ?? $rfc;
        $rfc = trim($rfc);
        $rfc = mb_strtoupper($rfc, 'UTF-8');
        $rfc = preg_replace('/\s+/u', '', $rfc) ?? $rfc;

        // Keep only characters that are ever valid inside an RFC: letters, Ñ, &, digits.
        $rfc = preg_replace('/[^A-Z0-9Ñ&]/u', '', $rfc) ?? $rfc;

        return $rfc;
    }
}

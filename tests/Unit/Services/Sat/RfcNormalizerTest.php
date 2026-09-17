<?php

namespace Tests\Unit\Services\Sat;

use App\Services\Sat\RfcNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RfcNormalizerTest extends TestCase
{
    private RfcNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new RfcNormalizer;
    }

    #[DataProvider('rfcProvider')]
    public function test_it_normalizes_rfc(?string $input, string $expected): void
    {
        $this->assertSame($expected, $this->normalizer->normalize($input));
    }

    public static function rfcProvider(): array
    {
        return [
            'lowercase with spaces' => ['  aaa010101aaa  ', 'AAA010101AAA'],
            'internal spaces' => ['AAA 010101 AAA', 'AAA010101AAA'],
            'already normalized' => ['AAA010101AAA', 'AAA010101AAA'],
            'zero width space' => ["AAA010101AAA\u{200B}", 'AAA010101AAA'],
            'non-breaking space' => ["AAA010101AAA\u{00A0}", 'AAA010101AAA'],
            'null input' => [null, ''],
            'strips invalid punctuation' => ['AAA-010101-AAA', 'AAA010101AAA'],
            'keeps eñe' => ['ÑAA010101AAA', 'ÑAA010101AAA'],
        ];
    }
}

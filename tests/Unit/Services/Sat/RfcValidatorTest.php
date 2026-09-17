<?php

namespace Tests\Unit\Services\Sat;

use App\Services\Sat\RfcValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RfcValidatorTest extends TestCase
{
    private RfcValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new RfcValidator;
    }

    #[DataProvider('validProvider')]
    public function test_valid_rfcs(string $rfc): void
    {
        $this->assertTrue($this->validator->isStructurallyValid($rfc));
    }

    public static function validProvider(): array
    {
        return [
            'persona moral' => ['AAA010101AAA'],
            'persona fisica' => ['AAAA010101AAA'],
            'generic consumer' => ['XAXX010101000'],
            'generic foreign' => ['XEXX010101000'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function test_invalid_rfcs(string $rfc): void
    {
        $this->assertFalse($this->validator->isStructurallyValid($rfc));
    }

    public static function invalidProvider(): array
    {
        return [
            'too short' => ['AAA0101'],
            'too long' => ['AAAAAAAAAAAAAAAA'],
            'invalid month' => ['AAA019901AAA'],
            'invalid day' => ['AAA010199AAA'],
            'empty' => [''],
            'letters only' => ['ABCDEFGHIJK'],
        ];
    }

    public function test_persona_fisica_vs_moral_detection(): void
    {
        $this->assertTrue($this->validator->isPersonaMoral('AAA010101AAA'));
        $this->assertFalse($this->validator->isPersonaFisica('AAA010101AAA'));

        $this->assertTrue($this->validator->isPersonaFisica('AAAA010101AAA'));
        $this->assertFalse($this->validator->isPersonaMoral('AAAA010101AAA'));
    }
}

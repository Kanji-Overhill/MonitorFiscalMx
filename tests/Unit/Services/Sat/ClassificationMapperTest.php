<?php

namespace Tests\Unit\Services\Sat;

use App\Enums\FiscalStatus;
use App\Services\Sat\ClassificationMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClassificationMapperTest extends TestCase
{
    private ClassificationMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new ClassificationMapper;
    }

    #[DataProvider('classificationProvider')]
    public function test_it_maps_raw_sat_text_to_classification(string $raw, string $expected): void
    {
        $this->assertSame($expected, $this->mapper->map($raw));
    }

    public static function classificationProvider(): array
    {
        return [
            'presunto' => ['Presunto', FiscalStatus::Presunto->value],
            'presuntos plural' => ['Presuntos', FiscalStatus::Presunto->value],
            'definitivo' => ['Definitivo', FiscalStatus::Definitivo->value],
            'definitivo lowercase' => ['definitivo', FiscalStatus::Definitivo->value],
            'desvirtuado' => ['Desvirtuado', FiscalStatus::Desvirtuado->value],
            'sentencia favorable' => ['Sentencia Favorable', FiscalStatus::SentenciaFavorable->value],
            'sentencia favorable with accents' => ['Sentencia Favorable (Art. 69-B)', FiscalStatus::SentenciaFavorable->value],
        ];
    }

    public function test_unrecognized_text_returns_null(): void
    {
        $this->assertNull($this->mapper->map('Columna sin relación alguna'));
        $this->assertNull($this->mapper->map(''));
    }
}

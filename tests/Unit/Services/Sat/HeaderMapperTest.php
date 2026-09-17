<?php

namespace Tests\Unit\Services\Sat;

use App\Services\Sat\HeaderMapper;
use Tests\TestCase;

class HeaderMapperTest extends TestCase
{
    public function test_it_maps_known_headers_regardless_of_order_and_accents(): void
    {
        $mapper = new HeaderMapper;

        $columns = $mapper->map([
            'No', 'RFC', 'Nombre del Contribuyente', 'Situación del contribuyente', 'Número y fecha de oficio', 'Fecha de publicación',
        ]);

        $this->assertSame(1, $columns['rfc']);
        $this->assertSame(2, $columns['business_name']);
        $this->assertSame(3, $columns['classification']);
        $this->assertSame(4, $columns['official_document']);
        $this->assertSame(5, $columns['publication_date']);
    }

    public function test_it_returns_empty_mapping_for_unrelated_headers(): void
    {
        $mapper = new HeaderMapper;

        $columns = $mapper->map(['Columna A', 'Columna B', 'Columna C']);

        $this->assertArrayNotHasKey('rfc', $columns);
        $this->assertArrayNotHasKey('classification', $columns);
    }

    public function test_it_maps_the_real_sat_per_stage_oficio_and_fecha_columns(): void
    {
        $mapper = new HeaderMapper;

        $columns = $mapper->map([
            'No', 'RFC', 'Nombre del Contribuyente', 'Situación del contribuyente',
            'Número y fecha de oficio global de presunción SAT', 'Publicación página SAT presuntos',
            'Número y fecha de oficio global de presunción DOF', 'Publicación DOF presuntos',
            'Número y fecha de oficio global de contribuyentes que desvirtuaron SAT', 'Publicación página SAT desvirtuados',
            'Número y fecha de oficio global de definitivos SAT', 'Publicación página SAT definitivos',
            'Número y fecha de oficio global de sentencia favorable SAT', 'Publicación página SAT sentencia favorable',
        ]);

        $this->assertSame(4, $columns['oficio_presunto']);
        $this->assertSame(5, $columns['fecha_presunto']);
        $this->assertSame(8, $columns['oficio_desvirtuado']);
        $this->assertSame(9, $columns['fecha_desvirtuado']);
        $this->assertSame(10, $columns['oficio_definitivo']);
        $this->assertSame(11, $columns['fecha_definitivo']);
        $this->assertSame(12, $columns['oficio_sentencia_favorable']);
        $this->assertSame(13, $columns['fecha_sentencia_favorable']);

        // Las columnas "DOF" no deben robar el lugar de las "SAT".
        $this->assertNotSame(6, $columns['oficio_presunto']);
        $this->assertNotSame(7, $columns['fecha_presunto']);
    }

    public function test_locate_header_row_skips_leading_disclaimer_rows(): void
    {
        $mapper = new HeaderMapper;

        $rows = [
            ['Aviso legal de ejemplo, no forma parte de la tabla', '', ''],
            ['Listado completo de contribuyentes', '', ''],
            ['No', 'RFC', 'Situación del contribuyente'],
            ['1', 'AAA010101AAA', 'Presunto'],
            ['2', 'BBB020202BB1', 'Definitivo'],
        ];

        $located = $mapper->locateHeaderRow($rows);

        $this->assertNotNull($located);
        $this->assertSame(['No', 'RFC', 'Situación del contribuyente'], $located['header']);
        $this->assertCount(2, $located['dataRows']);
        $this->assertSame('AAA010101AAA', $located['dataRows'][0][1]);
    }

    public function test_locate_header_row_returns_null_when_no_rfc_column_exists(): void
    {
        $mapper = new HeaderMapper;

        $located = $mapper->locateHeaderRow([
            ['Columna A', 'Columna B'],
            ['1', 'dato'],
        ]);

        $this->assertNull($located);
    }
}

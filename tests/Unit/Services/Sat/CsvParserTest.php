<?php

namespace Tests\Unit\Services\Sat;

use App\Services\Sat\CsvParser;
use Tests\TestCase;

class CsvParserTest extends TestCase
{
    public function test_it_parses_plain_utf8_csv(): void
    {
        $rows = (new CsvParser)->parse("RFC,Nombre\nAAA010101AAA,EMPRESA\n");

        $this->assertSame([
            ['RFC', 'Nombre'],
            ['AAA010101AAA', 'EMPRESA'],
        ], $rows);
    }

    public function test_it_converts_windows_1252_content_to_utf8(): void
    {
        $utf8 = "RFC,Nombre\nAAA010101AAA,AVALÚOS Y AMÉRICA\n";
        $windows1252 = mb_convert_encoding($utf8, 'Windows-1252', 'UTF-8');

        $rows = (new CsvParser)->parse($windows1252);

        $this->assertSame('AVALÚOS Y AMÉRICA', $rows[1][1]);
    }

    public function test_it_strips_a_utf8_bom(): void
    {
        $rows = (new CsvParser)->parse("\xEF\xBB\xBFRFC,Nombre\nAAA010101AAA,EMPRESA\n");

        $this->assertSame('RFC', $rows[0][0]);
    }

    public function test_it_detects_semicolon_delimiter(): void
    {
        $rows = (new CsvParser)->parse("RFC;Nombre\nAAA010101AAA;EMPRESA\n");

        $this->assertSame(['RFC', 'Nombre'], $rows[0]);
    }
}

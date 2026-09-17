<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Preparación para el monitoreo programado del listado 69-B (MVP: la
// actualización sigue siendo manual vía `php artisan sat:import-69b` o el
// botón "Consultar de nuevo" del widget). El comando ya valida que
// SAT_69B_SOURCE_URL esté configurada y falla de forma controlada si no lo
// está, así que es seguro dejar esta entrada activa; el `withoutOverlapping`
// evita ejecuciones concurrentes si una importación tarda más de un día.
Schedule::command('sat:import-69b')->dailyAt('03:00')->withoutOverlapping();

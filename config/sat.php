<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fuente oficial del listado del Artículo 69-B
    |--------------------------------------------------------------------------
    |
    | SAT_69B_SOURCE_URL debe apuntar al archivo CSV/descargable publicado
    | oficialmente por el SAT. SAT_69B_OFFICIAL_URL es la página informativa
    | oficial que se muestra al usuario como "fuente oficial".
    |
    | Verifica ambas URLs contra sat.gob.mx antes de usarlas en producción:
    | el SAT puede cambiar la ruta del archivo sin previo aviso.
    |
    */

    'source_url' => env('SAT_69B_SOURCE_URL'),

    'official_url' => env(
        'SAT_69B_OFFICIAL_URL',
        'https://www.sat.gob.mx/consultas/informacion-del-articulo-69-b-del-codigo-fiscal-de-la-federacion'
    ),

    'source_name' => 'SAT - Artículo 69-B',

    'download_timeout' => (int) env('SAT_69B_DOWNLOAD_TIMEOUT', 60),

    'disclaimer' => 'La información proviene de listados públicos del SAT y no sustituye una evaluación fiscal profesional.',

    /*
    |--------------------------------------------------------------------------
    | Endpoint HTTP opcional de importación
    |--------------------------------------------------------------------------
    |
    | Deshabilitado por defecto. El comando `php artisan sat:import-69b` es la
    | vía recomendada para ejecutar la importación. Solo habilita este
    | endpoint si necesitas dispararla remotamente (p. ej. un cron externo),
    | y hazlo detrás de un secreto largo y aleatorio.
    |
    */
    'import_endpoint' => [
        'enabled' => (bool) env('SAT_IMPORT_ENDPOINT_ENABLED', false),
        'secret' => env('SAT_IMPORT_ENDPOINT_SECRET'),
    ],
];

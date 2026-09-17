# Monitor Fiscal MX

Monitor SAT de proveedores para Zoho Books. Consulta y monitorea la
aparición de proveedores mexicanos en los listados públicos del SAT,
comenzando exclusivamente con el **Artículo 69-B**, desde un widget
contextual dentro de la ficha del proveedor en Zoho Books.

> Esta aplicación no está afiliada, respaldada ni certificada por el SAT.
> Utiliza información pública oficial y no sustituye la evaluación de un
> contador o asesor fiscal.

## Arquitectura

Monorepo con tres componentes:

1. **Backend (`/`, Laravel 12)** — API REST, normalización/validación de
   RFC, importación de los listados del SAT, historial por organización.
2. **Extensión/widget para Zoho Books (`extension/`)** — interfaz en
   `vendor.details.sidebar`. Solo consume la API; no descarga ni procesa
   archivos del SAT.
3. **Proceso de importación programado** — vive dentro del backend como
   comando Artisan (`php artisan sat:import-69b`) más el Laravel Scheduler,
   no como servicio aparte.

Ver [`docs/zoho-open-questions.md`](docs/zoho-open-questions.md) para todo
lo que aún debe confirmarse contra una cuenta real de Zoho antes de publicar.

## Requisitos

- PHP 8.2+
- Composer 2
- Node.js 18+ (solo para el widget de Zoho / assets del backend)
- MySQL/MariaDB (configuración por defecto de este repo — vía XAMPP en desarrollo) o SQLite/PostgreSQL si prefieres

## Instalación local del backend

```bash
composer install
cp .env.example .env   # si no existe ya
php artisan key:generate
```

Por defecto `.env.example` apunta a una base de datos MySQL/MariaDB local
llamada `proveedores_sat` (`DB_HOST=127.0.0.1`, `DB_USERNAME=root`,
`DB_PASSWORD=` vacío — credenciales por defecto de XAMPP). Crea la base de
datos si no existe:

```bash
# vía el cliente de XAMPP, o:
"C:\xampp82\mysql\bin\mysql.exe" -h 127.0.0.1 -u root -e "CREATE DATABASE IF NOT EXISTS proveedores_sat;"
```

Luego corre las migraciones:

```bash
php artisan migrate
```

Si prefieres SQLite para desarrollo, cambia `DB_CONNECTION=sqlite` en tu
`.env`, comenta las demás variables `DB_*` y crea el archivo con
`touch database/database.sqlite` antes de migrar.

### Variables de entorno relevantes

Ver `.env.example` para la lista completa. Las específicas de este producto:

| Variable | Descripción |
|---|---|
| `SAT_69B_SOURCE_URL` | URL del archivo descargable del listado 69-B. Verifícala contra sat.gob.mx antes de usarla en producción. Puede dejarse vacía si solo vas a importar desde archivos locales durante el desarrollo. |
| `SAT_69B_OFFICIAL_URL` | Página informativa oficial que se muestra como "fuente oficial" en las respuestas de la API. |
| `SAT_69B_DOWNLOAD_TIMEOUT` | Timeout (segundos) de la descarga. |
| `SAT_IMPORT_ENDPOINT_ENABLED` / `SAT_IMPORT_ENDPOINT_SECRET` | Endpoint HTTP opcional (`POST /api/v1/datasets/sat/import`), **deshabilitado por defecto**. Prefiere el comando de consola. |
| `CORS_ALLOWED_ORIGINS` | Orígenes permitidos para `/api/*`, solo relevante para pruebas locales del widget vía navegador. |

### Importar un dataset de prueba

```bash
php artisan sat:import-69b --file=database/fixtures/sat69b/sat69b_valid.csv
```

Fixtures disponibles en `database/fixtures/sat69b/` (todas anonimizadas,
ninguna es un archivo real del SAT):

- `sat69b_valid.csv` — cuatro registros válidos (uno por clasificación).
- `sat69b_updated.csv` — misma fuente con cambios, para probar que
  reemplaza (supersede) al dataset anterior.
- `sat69b_corrupt.csv` — texto sin estructura de columnas reconocible.
- `sat69b_unexpected_columns.csv` — encabezados que no coinciden con nada
  esperado.

`SAT_69B_SOURCE_URL` ya viene configurada con la URL oficial vigente del
"Listado completo" (Datos Abiertos del SAT), verificada de extremo a
extremo: descarga, decodifica (el archivo viene en Windows-1252, no UTF-8),
salta las filas de aviso legal antes del encabezado real, y elige el
oficio/fecha correctos por cada etapa (presunto/desvirtuado/definitivo/
sentencia favorable). Para importar el listado real:

```bash
php artisan sat:import-69b
```

El SAT puede mover esa URL sin previo aviso — si el comando falla,
verifícala contra
https://www.sat.gob.mx/minisitio/DatosAbiertos/contribuyentes_publicados.html
y actualiza `SAT_69B_SOURCE_URL`. Ya está programada diariamente a las 03:00
vía el scheduler de Laravel (`routes/console.php`).

### Provisionar una organización (para autenticar al widget)

```bash
php artisan organizations:provision <zoho_organization_id> "Nombre opcional"
```

Imprime una sola vez el token `{organization_id}|{token}` que debes
configurar como header `Authorization: Bearer ...` en el API Configuration
del widget (ver `extension/README.md`).

### Levantar el backend

```bash
composer run dev
```

Esto arranca `php artisan serve`, el worker de colas, `pail` (logs) y Vite
en paralelo. Alternativamente, solo el servidor HTTP:

```bash
php artisan serve
```

### Probar la API manualmente

```bash
curl http://localhost:8000/api/v1/health

curl http://localhost:8000/api/v1/rfcs/AAA010101AAA/status \
  -H "Authorization: Bearer 1|<token>"

curl http://localhost:8000/api/v1/rfcs/AAA010101AAA/history \
  -H "Authorization: Bearer 1|<token>"
```

### Ejecutar las pruebas

```bash
php artisan test
```

Cubre: normalización y validación estructural de RFC, mapeo de
clasificaciones del SAT, importación (válida, duplicada, corrupta, columnas
inesperadas, fuente no disponible, dataset reemplazado), consulta sin
coincidencia y con coincidencia (presunto/definitivo/desvirtuado/varias
coincidencias), aislamiento de historial por organización, autenticación de
la API y el contrato JSON estable.

## El widget de Zoho Books

Ver [`extension/README.md`](extension/README.md) para: activar Developer
Mode, ejecutar `zet run` localmente, configurar el API Configuration que
conecta el widget con este backend sin exponer secretos, y el procedimiento
de instalación como extensión privada.

## Estados fiscales

`sin_coincidencia`, `presunto`, `definitivo`, `desvirtuado`,
`sentencia_favorable`, `rfc_invalido`, `fuente_no_disponible`,
`error_consulta`. Nunca se usan etiquetas como "proveedor seguro" o
"proveedor fraudulento" — ver `app/Enums/FiscalStatus.php`.

## Validación de RFC

Solo validación **estructural** (longitud, formato persona física/moral,
plausibilidad de la fecha embebida). No se implementó validación de
homoclave/dígito verificador: no se encontró una fuente oficial
suficientemente clara y citable para ese algoritmo dentro del alcance de
este MVP. Ver `app/Services/Sat/RfcValidator.php`.

## Funciones simuladas o pendientes de autorización de Zoho

Documentado en detalle en
[`docs/zoho-open-questions.md`](docs/zoho-open-questions.md):

1. **Resuelto (no simulado):** el RFC del proveedor no está disponible para
   el widget — se confirmó en vivo que ni `ZFAPPS.get('contact')` ni la API
   de Zoho Books vía una Connection lo exponen. El usuario lo captura
   manualmente en el widget una vez por proveedor; el backend lo recuerda
   (`vendor_rfc_overrides`).
2. Soporte de Meta Fields para el módulo Vendor (no confirmado — por eso el
   historial vive en el backend, nunca en meta fields).
3. Escritura de custom fields desde `vendor.details.sidebar` (best-effort,
   no bloqueante).
4. Aprovisionamiento automático de custom fields al instalar la extensión.
5. URL exacta del SDK `zf_sdk.js` (dos variantes vistas en documentación
   oficial; se usó la citada literalmente en el código de ejemplo).
6. Publicación real en Zoho Sigma / Marketplace — no ejecutada, requiere
   autorización y acceso a cuenta del equipo.

## Fuera de alcance del MVP

Artículo 69, Artículo 69-B Bis, Opinión de cumplimiento, Constancia de
Situación Fiscal, descarga/cancelación de CFDI, facturación, reportes
financieros, cobros/suscripciones, integración con otros sistemas
contables, dashboard masivo.

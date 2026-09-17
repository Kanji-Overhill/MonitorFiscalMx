# Pendientes de verificación con Zoho (antes de publicar)

> **Nota:** la fuente de datos del SAT (formato del archivo, columnas,
> codificación) sí se verificó y probó de extremo a extremo contra el
> archivo real — ver "Fuente de datos del SAT (confirmado)" al final de este
> documento. Lo que sigue pendiente es exclusivamente del lado de Zoho.

Este documento reúne todo lo que **no se pudo confirmar contra documentación
oficial de Zoho** durante el desarrollo del MVP, y que requiere una cuenta
Zoho Books real en modo Developer para validarse. Nada de esto bloquea el
desarrollo del backend ni las pruebas automatizadas; sí bloquea la
publicación en producción.

## 1. El RFC del proveedor NO está disponible para el widget (confirmado, resuelto)

> Actualizado 2026-09-16 tras pruebas en vivo contra una cuenta real de
> Zoho Books México (`books.logatech.com.mx`) en Developer Mode.

- Confirmado en vivo: `ZFAPPS.get('contacts')` (plural) en
  `vendor.details.sidebar` devuelve una **lista** de contactos (`{contacts:
  Array(25)}`), no el proveedor actual.
- Confirmado en vivo: `ZFAPPS.get('contact')` (singular) sí devuelve
  específicamente el proveedor actual (`{contact: {...}}`, con el
  `contact_id` correcto), pero su objeto **no incluye el RFC bajo ningún
  nombre de campo** — se confirmó imprimiendo el JSON completo en consola.
  La UI completa de Zoho Books sí muestra el campo ("Número de registro del
  IVA (RFC...)"), pero el SDK del widget no lo expone.
- También se probó consultar la API REST de Zoho Books
  (`GET /books/v3/contacts/{id}`) desde el widget vía una Connection interna
  de Zoho Sigma (scope `ZohoBooks.fullaccess.all`, connection link
  `zohobooks`) — se abandonó esta ruta por decisión del usuario antes de
  confirmar el nombre del campo en esa respuesta, a favor de una solución
  más simple.
- **Decisión final:** el usuario captura el RFC manualmente en el propio
  widget la primera vez (formulario en `widget-view.js#renderRfcInput`). El
  backend lo recuerda por proveedor (`vendor_rfc_overrides`, endpoints
  `GET`/`PUT /api/v1/vendors/{zoho_vendor_id}/rfc`), así que solo se captura
  una vez por proveedor y organización. El widget permite corregirlo
  después con el botón "Editar RFC".
- Esto ya no es un pendiente de verificación: es el comportamiento
  definitivo del MVP.

## 2. Meta Fields no confirmados para el módulo Vendor

- Confirmado: Meta Fields están documentados para Invoices, Quotes, Sales
  Orders y Credit Notes.
- No confirmado: soporte de Meta Fields para Vendors/Contacts.
- Decisión de diseño (no bloqueante): el historial completo de consultas
  vive en el backend (tabla `vendor_checks`), nunca en meta fields. Los
  cuatro custom fields (`cf_monitor_fiscal_estado`, `cf_monitor_fiscal_lista`,
  `cf_monitor_fiscal_fecha`, `cf_monitor_fiscal_actualizado`) solo reflejan
  el último resultado, y su escritura desde el widget es best-effort (ver
  punto 3).

## 3. Escritura de custom fields desde `vendor.details.sidebar`

- No confirmado: si `ZFAPPS.set(...)` puede escribir custom fields del
  contacto actual desde esta ubicación (la documentación solo muestra un
  ejemplo de `set` para direcciones de facturación en un widget modal de
  `customer.creation.sidebar`).
- Mitigación implementada: `zoho-context.js#trySyncCustomFields` envuelve la
  llamada en try/catch (vía `.catch()`) y no interrumpe el flujo del widget
  si falla. El estado del proveedor siempre es correcto porque se lee del
  backend, no de los custom fields.
- Acción antes de publicar: confirmar el método correcto, o documentar el
  alta manual de estos cuatro custom fields como paso de configuración.

## 4. Aprovisionamiento automático de custom fields en la instalación

- No confirmado con una cita oficial concreta si `plugin-manifest.json`
  puede declarar la creación automática de custom fields al instalar la
  extensión.
- Mitigación: por ahora, `plugin-manifest.json` no declara custom fields.
  Si Zoho Sigma expone esa capacidad al momento de crear el proyecto ahí,
  añadirla; si no, documentar el alta manual en el README de instalación
  como paso previo a instalar la extensión.

## 5. URL exacta del SDK (`zf_sdk.js`)

Se encontraron dos URLs en páginas oficiales de Zoho:

- `https://static.zohocdn.com/zohofinance/v1.0/zf_sdk.js` (citada
  verbatim en la página "Sample Widget" de Zoho Books).
- `https://js.zohostatic.com/zohofinance/v1/zf_sdk.js` (mencionada en otro
  resultado de búsqueda sobre el SDK).

`extension/app/widgets/vendor-sidebar/index.html` usa la primera por venir
de una cita directa del código de ejemplo oficial. `zet init` (autenticado)
genera el `<script>` correcto automáticamente — úsalo para confirmar/():

```bash
zet login
zet init --zoho-service books --project-name monitor-fiscal-mx-extension
```

## 6. `zet run` / config local de desarrollo

`zet validate` funciona sin sesión iniciada y confirma que
`plugin-manifest.json` y la estructura de carpetas son válidas. `zet run`
(el servidor de desarrollo local en `https://127.0.0.1:5000`) requiere un
archivo de configuración adicional que la CLI solo genera tras
`zet login` + `zet init` autenticado — no se pudo generar en este entorno
porque requiere las credenciales de Zoho del usuario. Ejecuta ambos comandos
tú mismo antes de la primera prueba local (ver `extension/README.md`).

## 7. Aprovisionamiento y distribución (Zoho Sigma / Marketplace)

Confirmado: publicación privada (URL con hash, sin revisión) y pública (con
revisión, sube a Marketplace). No se ejecutó ningún paso de publicación real
en este trabajo — requiere acceso a una cuenta de Zoho Sigma del equipo, y
no se debe hacer sin autorización explícita y en un entorno no productivo
primero.

## Fuente de datos del SAT (confirmado)

A diferencia de los puntos anteriores (que son de Zoho), esto sí se
verificó descargando y procesando el archivo real:

- URL oficial del "Listado completo" (Datos Abiertos, minisitio del SAT):
  `https://wu1agsprosta001.blob.core.windows.net/agsc-publicaciones/Datos_abiertos/Documents_AGAFF/Listado_completo_69-B.csv`,
  enlazada desde
  https://www.sat.gob.mx/minisitio/DatosAbiertos/contribuyentes_publicados.html.
  Ya está configurada en `.env.example`/`.env` como `SAT_69B_SOURCE_URL`.
- El archivo real trae **dos filas de aviso legal/título antes del
  encabezado real** — el importador ahora busca la fila que contiene "RFC"
  en vez de asumir que es la primera (`HeaderMapper::locateHeaderRow`).
- Está codificado en **Windows-1252/ISO-8859-1**, no UTF-8 — `CsvParser`
  detecta y convierte automáticamente.
- Trae **un par de columnas "oficio" / "fecha de publicación" por cada
  etapa** (presunto, desvirtuado, definitivo, sentencia favorable) en vez de
  una sola columna genérica — `Sat69BImporter::resolveStageColumns` elige el
  par correcto según la clasificación de cada fila.
- Los nombres de razón social pueden superar los 255 caracteres; las
  columnas `business_name` y `official_document` de `sat_records` son
  `TEXT`, no `VARCHAR(255)`.
- Prueba real ejecutada el 2026-09-16: 14,850 filas de datos, 14,591
  importadas correctamente, 246 omitidas (RFC con formato no reconocido o
  clasificación no interpretable — se registran en `error_message` del
  dataset, no rompen la importación). Distribución: 11,956 definitivos, 1,516
  sentencia favorable, 779 presuntos, 340 desvirtuados.
- El archivo oficial completo **no se subió a este repositorio** (solo
  fixtures pequeños y anonimizados en `database/fixtures/sat69b/`), conforme
  a las instrucciones del proyecto.

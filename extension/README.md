# Monitor Fiscal MX — Extensión / Widget para Zoho Books

Widget de sidebar (`vendor.details.sidebar`) que muestra la situación de un
proveedor mexicano frente al listado público del Artículo 69-B del SAT,
consultando el backend Laravel del monorepo (`../` desde esta carpeta).

> Esta extensión no está afiliada, respaldada ni certificada por el SAT.
> Usa información pública oficial y no sustituye la evaluación de un
> contador o asesor fiscal.

## Antes de empezar: pendientes de verificación

Varias piezas del SDK de Zoho no se pudieron confirmar 100% contra
documentación oficial durante el desarrollo (nombre exacto del campo RFC,
soporte de `ZFAPPS.get('contacts')` en esta ubicación específica, escritura
de custom fields). Todas están documentadas, con su mitigación, en
[`../docs/zoho-open-questions.md`](../docs/zoho-open-questions.md). Revísalo
antes de publicar.

## Estructura

```
extension/
├── plugin-manifest.json          # ubicación, servicio, widgets
├── package.json                  # metadata mínima para `zet run`
└── app/
    ├── images/app_icon.svg
    └── widgets/vendor-sidebar/
        ├── index.html
        ├── styles.css
        ├── zoho-context.js       # toda la interacción con ZFAPPS
        ├── monitor-fiscal-client.js  # llamadas al backend vía API Configurations
        ├── widget-view.js        # renderizado / estados visuales
        └── main.js               # orquestación
```

## Requisitos

- Cuenta de Zoho con acceso a **Zoho Sigma** (developer.zoho.com) y a una
  organización de **Zoho Books** (idealmente edición México) donde tengas
  permiso de administrador para activar Developer Mode.
- Node.js 18+ y la CLI `zet` (`npm install -g zoho-extension-toolkit`, o el
  paquete equivalente vigente — confírmalo en la documentación de Zoho, ya
  que el nombre del paquete npm puede cambiar).
- El backend corriendo (ver `../README.md`) con al menos un dataset 69-B
  importado.

## 1. Autenticarte con tu cuenta de Zoho

```bash
zet login
```

Esto es una acción de cuenta que debes realizar tú mismo con tus propias
credenciales; ningún agente automatizado debe hacerlo por ti.

## 2. Regenerar el scaffold oficial (recomendado)

Aunque este repositorio ya trae `plugin-manifest.json` y el widget escritos
a mano (y `zet validate` los aprueba), es buena práctica correr una vez:

```bash
cd extension
zet init --zoho-service books --project-name monitor-fiscal-mx-extension
```

en una carpeta aparte, y comparar el `plugin-manifest.json` y el
`<script src="...">` del SDK generados contra los de este repo — así
confirmas la URL exacta del SDK y cualquier archivo de configuración local
adicional que la CLI necesite para `zet run` (ver punto 6 del documento de
pendientes).

## 3. Activar Developer Mode en Zoho Books

Settings → Developer Space → Widgets → activa el interruptor de Developer
Mode. Mientras esté activo, otros widgets instalados quedarán inactivos.

## 4. Ejecutar el widget localmente

```bash
cd extension
zet run
```

Verifica en el navegador: `https://127.0.0.1:5000/plugin-manifest.json`.
Luego abre la ficha de un proveedor en tu organización de Zoho Books de
prueba — el widget debería cargar en el sidebar.

## 5. Configurar la comunicación segura con el backend (API Configurations)

El widget **nunca** llama directamente a la URL del backend ni guarda
tokens en su JavaScript. Usa el mecanismo oficial de Zoho Sigma:

1. En tu proyecto de extensión en Zoho Sigma, ve a la pestaña
   **API Configurations** → **+ New API Configuration**.
2. Crea `ac_monitor_fiscal_status`:
   - Método: `GET`
   - URL: `{API_BASE_URL}/api/v1/rfcs/${url_param.rfc}/status`
   - Header: `Authorization: Bearer {organization_id}|{token}` (genera el
     token con `php artisan organizations:provision <zoho_organization_id>`
     en el backend — ver `../README.md`).
3. Crea `ac_monitor_fiscal_history` de forma análoga, apuntando a
   `{API_BASE_URL}/api/v1/rfcs/${url_param.rfc}/history`.
4. Crea `ac_monitor_fiscal_vendor_rfc_get`:
   - Método: `GET`
   - URL: `{API_BASE_URL}/api/v1/vendors/${url_param.zoho_vendor_id}/rfc`
   - Mismo header `Authorization` que las anteriores.
5. Crea `ac_monitor_fiscal_vendor_rfc_save`:
   - Método: `PUT`
   - URL: `{API_BASE_URL}/api/v1/vendors/${url_param.zoho_vendor_id}/rfc?rfc=${url_query.rfc}`
   - Mismo header `Authorization`.
6. Los nombres deben coincidir exactamente con las constantes
   `API_CONFIG_*` en `app/widgets/vendor-sidebar/monitor-fiscal-client.js`.

**Por qué hay un "guardar RFC" además de "consultar":** se confirmó en
Developer Mode, contra una cuenta real, que el SDK del widget no expone el
RFC del proveedor (ni `ZFAPPS.get('contact')`, ni la API de Zoho Books vía
una Connection). El widget lo captura manualmente la primera vez y el
backend lo recuerda por proveedor — ver
[docs/zoho-open-questions.md](../docs/zoho-open-questions.md), punto 1.

Para desarrollo local sin acceso a Sigma, puedes probar el backend
directamente con `curl` (ver `../README.md`), pero el widget en sí no debe
apuntar a URLs hardcodeadas ni a tokens embebidos.

## 6. CSP / dominios permitidos

**Confirmado en vivo:** Zoho aplica una Content Security Policy al iframe del
widget que por defecto solo permite `connect-src` hacia sus propios dominios
(`*.zappsusercontent.com`, `*.zohostatic.com`, `*.sigmausercontent.com`,
`*.qntrlusercontent.com`). Cualquier `fetch()` directo del widget a un
dominio externo (como nuestro backend) se bloquea **aunque el backend tenga
CORS bien configurado** — es una restricción de la plataforma, no del
servidor.

Si usas API Configurations (recomendado, ver punto 5), esto no aplica: la
llamada la hace el servidor de Zoho, no el navegador, así que nunca choca con
esta CSP.

Si en cambio el widget llama directo con `fetch()` (atajo usado para
pruebas rápidas, no recomendado para publicar — ver punto 5), tienes que
declarar el dominio del backend en `plugin-manifest.json`:

```json
"cspDomains": {
  "connect-src": ["https://tu-backend.example.com"]
}
```

Ya está configurado así en este repo con
`https://monitorfiscalmx-production.up.railway.app`.

## 7. Instalación como extensión privada

1. En Zoho Sigma, con la extensión probada, usa **Publish** → visibilidad
   **Private**. Se genera una URL con hash.
2. Instala esa URL en la organización de Zoho Books donde quieras probar de
   forma privada (no productiva salvo autorización explícita).
3. La publicación pública (Marketplace) requiere el formulario de revisión
   de Zoho — no forma parte del MVP.

## Custom fields del proveedor (opcional, best-effort)

Si quieres que el widget refleje el último resultado directamente en la
ficha del proveedor, da de alta estos cuatro custom fields en Zoho Books
(Settings → Customization → Contacts → Vendors) — el aprovisionamiento
automático desde el manifiesto no está confirmado (pendiente #5):

- `cf_monitor_fiscal_estado`
- `cf_monitor_fiscal_lista`
- `cf_monitor_fiscal_fecha`
- `cf_monitor_fiscal_actualizado`

El widget intenta escribirlos tras cada consulta, mostrando la información
correcta en su propia interfaz incluso si esa escritura falla.

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Monitor Fiscal MX — Simulador del widget (dev)</title>
  <link rel="stylesheet" href="/demo/styles.css" />
</head>
<body>
  <div class="mf-demo-shell">
    <div class="mf-demo-controls">
      <label for="vendor-id-input">Vendor ID (simulado, como si viniera de Zoho)</label>
      <input id="vendor-id-input" type="text" value="demo-vendor-1" />
      <button id="vendor-id-load" class="mf-btn mf-btn--primary" type="button">Cargar proveedor</button>
      <p style="font-size:11px;color:#6b7280;margin:4px 0 0;">
        Esta página solo existe en entorno local (<code>APP_ENV=local</code>) y llama
        directamente a <code>/api/v1/*</code> con un token de la organización
        "demo-widget" generado al cargar la página. No usar en producción.
      </p>
    </div>

    <div class="mf-demo-frame">
      <div id="monitor-fiscal-root" class="mf-widget">
        <div class="mf-loading">Cargando…</div>
      </div>
    </div>
  </div>

  <script>window.MONITOR_FISCAL_DEMO_TOKEN = @json($bearerToken);</script>
  <script src="/demo/widget-view.js"></script>
  <script src="/demo/demo-client.js"></script>
  <script src="/demo/demo-main.js"></script>
  <script>
    (function () {
      var input = document.getElementById('vendor-id-input');
      var button = document.getElementById('vendor-id-load');

      function load() {
        MonitorFiscalDemo.loadVendor(input.value.trim() || 'demo-vendor-1');
      }

      button.addEventListener('click', load);
      load();
    })();
  </script>
</body>
</html>

/**
 * Aísla toda la interacción con el SDK de Zoho (ZFAPPS) del resto del widget.
 *
 * Métodos usados y su fuente:
 *  - ZFAPPS.extension.init()  → confirmado en la página oficial "Sample Widget"
 *    de Zoho Books.
 *  - ZFAPPS.get('contact')    → confirmado en vivo (Developer Mode, cuenta
 *    real): devuelve el proveedor actual en vendor.details.sidebar bajo
 *    `{ contact: {...} }`. OJO: `ZFAPPS.get('contacts')` (plural) devuelve
 *    una LISTA de contactos, no el actual — no usar esa variante aquí.
 *  - ZFAPPS.request(...)      → confirmado en "API Configurations for Widgets".
 *
 * El objeto que devuelve `ZFAPPS.get('contact')` NO incluye el RFC del
 * proveedor (confirmado en vivo contra una cuenta real de México, con y sin
 * una Connection a la API de Zoho Books de por medio) — Zoho no expone ese
 * dato fiscal al JS del widget. Por eso el RFC se captura manualmente en el
 * propio widget (ver main.js) en vez de leerlo del contexto de Zoho.
 */
(function (global) {
  'use strict';

  function init() {
    if (!global.ZFAPPS) {
      return Promise.reject(new Error('ZFAPPS SDK no disponible (¿widget cargado fuera de Zoho Books?)'));
    }

    return global.ZFAPPS.extension.init().then(function (app) {
      return { app: app };
    });
  }

  function getVendorContext() {
    return global.ZFAPPS.get('contact').then(function (response) {
      var contact = (response && response.contact) || {};

      return {
        vendorId: contact.contact_id || null,
        vendorName: contact.company_name || contact.contact_name || null,
      };
    });
  }

  /**
   * Intento best-effort de reflejar el último resultado en los custom fields
   * del proveedor. No confirmado oficialmente para vendor.details.sidebar:
   * si el SDK rechaza la llamada, se ignora sin romper el widget.
   */
  function trySyncCustomFields(snapshot) {
    if (!global.ZFAPPS || typeof global.ZFAPPS.set !== 'function') {
      return Promise.resolve(false);
    }

    return global.ZFAPPS.set('contacts.custom_fields', {
      cf_monitor_fiscal_estado: snapshot.status,
      cf_monitor_fiscal_lista: snapshot.matched ? 'Artículo 69-B' : '',
      cf_monitor_fiscal_fecha: (snapshot.matches && snapshot.matches[0] && snapshot.matches[0].publication_date) || '',
      cf_monitor_fiscal_actualizado: snapshot.checked_at,
    }).then(function () {
      return true;
    }).catch(function () {
      return false;
    });
  }

  global.MonitorFiscalZohoContext = {
    init: init,
    getVendorContext: getVendorContext,
    trySyncCustomFields: trySyncCustomFields,
  };
})(window);

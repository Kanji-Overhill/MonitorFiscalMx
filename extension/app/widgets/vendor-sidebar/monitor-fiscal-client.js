/**
 * Cliente para el backend Monitor Fiscal MX, usando exclusivamente el
 * mecanismo oficial "API Configurations" del SDK (ZFAPPS.request), que
 * mantiene la URL real del backend y cualquier secreto fuera del JS del
 * widget. Ver: zoho.com/books/developer/widgets/api-configurations.html
 *
 * Los nombres "ac_monitor_fiscal_*" deben coincidir exactamente con los API
 * Configurations creados en Zoho Sigma (ver extension/README.md).
 */
(function (global) {
  'use strict';

  var API_CONFIG_STATUS = 'ac_monitor_fiscal_status';
  var API_CONFIG_HISTORY = 'ac_monitor_fiscal_history';
  var API_CONFIG_VENDOR_RFC_GET = 'ac_monitor_fiscal_vendor_rfc_get';
  var API_CONFIG_VENDOR_RFC_SAVE = 'ac_monitor_fiscal_vendor_rfc_save';

  function request(apiConfigurationKey, urlParam, urlQuery) {
    if (!global.ZFAPPS || typeof global.ZFAPPS.request !== 'function') {
      return Promise.reject(new Error('ZFAPPS.request no disponible.'));
    }

    return global.ZFAPPS.request({
      api_configuration_key: apiConfigurationKey,
      url_param: urlParam || {},
      url_query: urlQuery || {},
    });
  }

  function getStatus(rfc, zohoVendorId) {
    return request(API_CONFIG_STATUS, { rfc: rfc }, { zoho_vendor_id: zohoVendorId });
  }

  function getHistory(rfc, zohoVendorId) {
    return request(API_CONFIG_HISTORY, { rfc: rfc }, { zoho_vendor_id: zohoVendorId, limit: 10 });
  }

  function getVendorRfc(zohoVendorId) {
    return request(API_CONFIG_VENDOR_RFC_GET, { zoho_vendor_id: zohoVendorId });
  }

  function saveVendorRfc(zohoVendorId, rfc) {
    return request(API_CONFIG_VENDOR_RFC_SAVE, { zoho_vendor_id: zohoVendorId }, { rfc: rfc });
  }

  global.MonitorFiscalClient = {
    getStatus: getStatus,
    getHistory: getHistory,
    getVendorRfc: getVendorRfc,
    saveVendorRfc: saveVendorRfc,
  };
})(window);

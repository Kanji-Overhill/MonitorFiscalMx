/**
 * Cliente para el simulador de desarrollo: llama directamente a nuestra API
 * vía fetch() (mismo origen, sin CORS) en vez de ZFAPPS.request(). Expone
 * la misma interfaz que extension/app/widgets/vendor-sidebar/monitor-fiscal-client.js
 * para poder reutilizar la misma lógica de orquestación.
 */
(function (global) {
  'use strict';

  var bearerToken = global.MONITOR_FISCAL_DEMO_TOKEN;

  function request(method, path) {
    return fetch(path, {
      method: method,
      headers: { Authorization: 'Bearer ' + bearerToken },
    }).then(function (response) {
      return response.json().catch(function () {
        return null;
      }).then(function (body) {
        if (!response.ok) {
          return Promise.reject({ status: response.status, body: body });
        }

        return body;
      });
    });
  }

  function getStatus(rfc, zohoVendorId) {
    var qs = zohoVendorId ? ('?zoho_vendor_id=' + encodeURIComponent(zohoVendorId)) : '';

    return request('GET', '/api/v1/rfcs/' + encodeURIComponent(rfc) + '/status' + qs);
  }

  function getHistory(rfc, zohoVendorId) {
    var qs = zohoVendorId ? ('?zoho_vendor_id=' + encodeURIComponent(zohoVendorId)) : '';

    return request('GET', '/api/v1/rfcs/' + encodeURIComponent(rfc) + '/history' + qs);
  }

  function getVendorRfc(zohoVendorId) {
    return request('GET', '/api/v1/vendors/' + encodeURIComponent(zohoVendorId) + '/rfc');
  }

  function saveVendorRfc(zohoVendorId, rfc) {
    return request('PUT', '/api/v1/vendors/' + encodeURIComponent(zohoVendorId) + '/rfc?rfc=' + encodeURIComponent(rfc));
  }

  global.MonitorFiscalClient = {
    getStatus: getStatus,
    getHistory: getHistory,
    getVendorRfc: getVendorRfc,
    saveVendorRfc: saveVendorRfc,
  };
})(window);

/**
 * Orquestación del widget (versión simplificada): pide el RFC al usuario,
 * lo manda a consultar contra el backend, y muestra el resultado. Sin
 * persistencia por ahora (eso se retoma después) — lo esencial primero.
 */
(function (global) {
  'use strict';

  var Zoho = global.MonitorFiscalZohoContext;
  var Client = global.MonitorFiscalClient;
  var View = global.MonitorFiscalView;

  var currentContext = null; // { vendorId, vendorName, rfc }
  var currentResult = null;

  function showRfcInput(errorMessage) {
    View.renderRfcInput({
      initialValue: currentContext && currentContext.rfc,
      errorMessage: errorMessage,
      onSubmit: handleRfcSubmit,
    });
  }

  function handleRfcSubmit(rawValue) {
    if (!rawValue || !rawValue.trim()) {
      showRfcInput('Escribe un RFC.');
      return;
    }

    currentContext.rfc = rawValue.trim();
    runQuery();
  }

  function runQuery() {
    View.renderLoading();

    if (!currentContext || !currentContext.rfc) {
      showRfcInput();
      return;
    }

    Client.getStatus(currentContext.rfc, currentContext.vendorId)
      .then(function (result) {
        currentResult = result;
        View.renderResult(result, {
          onRetry: runQuery,
          onHistory: showHistory,
          onEditRfc: function () {
            showRfcInput();
          },
        });
      })
      .catch(function (error) {
        View.renderError(describeError(error), runQuery);
      });
  }

  function showHistory() {
    if (!currentContext || !currentContext.rfc) {
      showRfcInput();
      return;
    }

    View.renderLoading();

    Client.getHistory(currentContext.rfc, currentContext.vendorId)
      .then(function (response) {
        View.renderHistory(response && response.history, function () {
          if (currentResult) {
            View.renderResult(currentResult, {
              onRetry: runQuery,
              onHistory: showHistory,
              onEditRfc: function () {
                showRfcInput();
              },
            });
          } else {
            runQuery();
          }
        });
      })
      .catch(function (error) {
        View.renderError(describeError(error), showHistory);
      });
  }

  function describeError(error) {
    if (error && error.status === 429) {
      return 'Se alcanzó el límite de consultas por minuto. Intenta de nuevo en unos segundos.';
    }

    return 'Ocurrió un error temporal al consultar. Intenta nuevamente.';
  }

  Zoho.init()
    .then(function () {
      return Zoho.getVendorContext();
    })
    .then(function (context) {
      currentContext = { vendorId: context.vendorId, vendorName: context.vendorName, rfc: null };
      showRfcInput();
    })
    .catch(function (error) {
      // eslint-disable-next-line no-console
      console.error('[MonitorFiscalMX] error de inicialización:', error);
      View.renderError('No se pudo inicializar el widget dentro de Zoho Books.', function () {
        global.location.reload();
      });
    });
})(window);

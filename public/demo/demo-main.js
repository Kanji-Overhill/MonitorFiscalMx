/**
 * Orquestación del simulador (versión simplificada, espejo de
 * extension/app/widgets/vendor-sidebar/main.js): pide el RFC, lo consulta,
 * muestra el resultado. El "vendor_id" viene de un campo de texto en la
 * página en vez del contexto de Zoho.
 */
(function (global) {
  'use strict';

  var Client = global.MonitorFiscalClient;
  var View = global.MonitorFiscalView;

  var currentContext = null; // { vendorId, rfc }
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

  function loadVendor(vendorId) {
    currentContext = { vendorId: vendorId, rfc: null };
    showRfcInput();
  }

  global.MonitorFiscalDemo = {
    loadVendor: loadVendor,
  };
})(window);

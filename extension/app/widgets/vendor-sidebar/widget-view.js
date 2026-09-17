/**
 * Renderizado del widget. Sin dependencias externas: DOM plano.
 * Cada estado visual combina color + icono + texto (nunca solo color).
 */
(function (global) {
  'use strict';

  var STATUS_META = {
    sin_consulta: { color: 'gray', icon: '○', label: 'Sin consulta' },
    sin_coincidencia: { color: 'green', icon: '✔', label: 'Sin coincidencias' },
    presunto: { color: 'yellow', icon: '⚠', label: 'Presunto' },
    definitivo: { color: 'red', icon: '✕', label: 'Definitivo' },
    desvirtuado: { color: 'blue', icon: 'ℹ', label: 'Desvirtuado' },
    sentencia_favorable: { color: 'blue', icon: 'ℹ', label: 'Sentencia favorable' },
    rfc_invalido: { color: 'orange', icon: '⚠', label: 'RFC inválido' },
    fuente_no_disponible: { color: 'orange', icon: '⚠', label: 'Fuente del SAT no disponible' },
    error_consulta: { color: 'orange', icon: '⚠', label: 'Error al consultar' },
  };

  var root = document.getElementById('monitor-fiscal-root');

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;
    return node;
  }

  function clear() {
    root.innerHTML = '';
  }

  function header() {
    var h = el('div', 'mf-header');
    h.appendChild(el('span', 'mf-title', 'Monitor Fiscal MX'));
    return h;
  }

  function disclaimer(text) {
    return el('p', 'mf-disclaimer', text || 'Esta información proviene de fuentes públicas del SAT. No sustituye la evaluación de un contador o asesor fiscal. Monitor Fiscal MX no está afiliado, respaldado ni certificado por el SAT.');
  }

  function badge(statusKey) {
    var meta = STATUS_META[statusKey] || STATUS_META.sin_consulta;
    var b = el('div', 'mf-badge mf-badge--' + meta.color);
    b.appendChild(el('span', 'mf-badge-icon', meta.icon));
    b.appendChild(el('span', 'mf-badge-label', meta.label));
    return b;
  }

  function renderLoading() {
    clear();
    root.appendChild(header());
    root.appendChild(el('div', 'mf-loading', 'Consultando…'));
  }

  /**
   * @param {Object} options
   * @param {string|null} options.initialValue RFC ya guardado, para editarlo.
   * @param {string|null} options.errorMessage Error de la última captura (si hubo).
   * @param {function(string): void} options.onSubmit
   */
  function renderRfcInput(options) {
    options = options || {};
    clear();
    root.appendChild(header());
    root.appendChild(badge('rfc_invalido'));
    root.appendChild(el(
      'p',
      'mf-message',
      options.initialValue
        ? 'Corrige el RFC de este proveedor.'
        : 'No se encontró un RFC en la ficha de este proveedor. Captúralo para consultar el listado 69-B.',
    ));

    if (options.errorMessage) {
      root.appendChild(el('p', 'mf-input-error', options.errorMessage));
    }

    var form = el('form', 'mf-rfc-form');
    var input = document.createElement('input');
    input.type = 'text';
    input.className = 'mf-input';
    input.placeholder = 'Ej. ABC010203XYZ';
    input.maxLength = 13;
    input.autocapitalize = 'characters';
    input.value = options.initialValue || '';
    form.appendChild(input);

    var submitBtn = el('button', 'mf-btn mf-btn--primary', 'Consultar');
    submitBtn.type = 'submit';
    form.appendChild(submitBtn);

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (options.onSubmit) {
        options.onSubmit(input.value);
      }
    });

    root.appendChild(form);
    root.appendChild(disclaimer());
  }

  function renderError(message, onRetry) {
    clear();
    root.appendChild(header());
    root.appendChild(badge('error_consulta'));
    root.appendChild(el('p', 'mf-message', message || 'Ocurrió un error temporal al consultar. Intenta nuevamente.'));
    root.appendChild(buildActions([{ label: 'Reintentar', onClick: onRetry }]));
    root.appendChild(disclaimer());
  }

  function formatDate(isoOrDateString) {
    if (!isoOrDateString) return '—';
    var d = new Date(isoOrDateString);
    if (isNaN(d.getTime())) return isoOrDateString;
    return d.toLocaleDateString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit' });
  }

  function buildActions(actions) {
    var wrap = el('div', 'mf-actions');
    actions.filter(Boolean).forEach(function (action) {
      var btn = el('button', 'mf-btn' + (action.primary ? ' mf-btn--primary' : ''), action.label);
      btn.type = 'button';
      btn.addEventListener('click', action.onClick);
      wrap.appendChild(btn);
    });
    return wrap;
  }

  function renderResult(result, handlers) {
    clear();
    root.appendChild(header());
    root.appendChild(badge(result.status));

    var meta = el('dl', 'mf-meta');
    var addRow = function (term, value) {
      meta.appendChild(el('dt', null, term));
      meta.appendChild(el('dd', null, value));
    };

    addRow('RFC', result.rfc);
    addRow('Última consulta', formatDate(result.checked_at));
    addRow('Fuente actualizada', formatDate(result.source && result.source.dataset_updated_at));

    root.appendChild(meta);

    if (result.matched && result.matches && result.matches.length) {
      var list = el('div', 'mf-matches');
      result.matches.forEach(function (match) {
        var card = el('div', 'mf-match-card');
        if (match.business_name) card.appendChild(el('p', 'mf-match-name', match.business_name));
        card.appendChild(el('p', 'mf-match-line', 'Listado: Artículo 69-B — ' + (STATUS_META[match.classification] || {}).label));
        card.appendChild(el('p', 'mf-match-line', 'Publicación: ' + formatDate(match.publication_date)));
        if (match.official_document) {
          card.appendChild(el('p', 'mf-match-line', 'Oficio: ' + match.official_document));
        }
        list.appendChild(card);
      });
      root.appendChild(list);
    } else if (!result.matched && result.status === 'sin_coincidencia') {
      root.appendChild(el('p', 'mf-message', 'Sin coincidencias en los listados consultados.'));
    } else if (result.matched) {
      root.appendChild(el('p', 'mf-message', 'Se encontró una coincidencia para este RFC en un listado público oficial del SAT.'));
    }

    root.appendChild(el('p', 'mf-review-note', 'Este resultado debe ser revisado por un profesional fiscal.'));

    root.appendChild(buildActions([
      { label: 'Consultar de nuevo', primary: true, onClick: handlers.onRetry },
      { label: 'Ver historial', onClick: handlers.onHistory },
      result.matched && result.source && result.source.official_url
        ? { label: 'Abrir fuente oficial', onClick: handlers.onOpenSource }
        : null,
      { label: 'Editar RFC', onClick: handlers.onEditRfc },
    ]));

    root.appendChild(disclaimer(result.disclaimer));
  }

  function renderHistory(items, onBack) {
    clear();
    root.appendChild(header());
    root.appendChild(el('h3', 'mf-subtitle', 'Historial de consultas'));

    if (!items || !items.length) {
      root.appendChild(el('p', 'mf-message', 'Aún no hay historial registrado para este proveedor.'));
    } else {
      var list = el('ul', 'mf-history-list');
      items.forEach(function (item) {
        var li = el('li', 'mf-history-item');
        var meta = STATUS_META[item.status] || STATUS_META.sin_consulta;
        li.appendChild(el('span', 'mf-history-date', formatDate(item.checked_at)));
        var b = el('span', 'mf-badge mf-badge--' + meta.color + ' mf-badge--sm');
        b.appendChild(el('span', 'mf-badge-icon', meta.icon));
        b.appendChild(el('span', 'mf-badge-label', meta.label));
        li.appendChild(b);
        list.appendChild(li);
      });
      root.appendChild(list);
    }

    root.appendChild(buildActions([{ label: 'Volver', onClick: onBack }]));
  }

  global.MonitorFiscalView = {
    renderLoading: renderLoading,
    renderRfcInput: renderRfcInput,
    renderError: renderError,
    renderResult: renderResult,
    renderHistory: renderHistory,
  };
})(window);

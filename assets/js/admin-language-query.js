  (function (wp) {
    var __ =
      wp && wp.i18n && wp.i18n.__
        ? wp.i18n.__
        : function (text) {
            return text;
          };

    function init() {
      var card = document.querySelector('.tpre-language-query-card');
      var inputEl = document.getElementById('tpre-language-query-input');
      var engineEl = document.getElementById('tpre-language-query-engine');
      var buttonEl = document.getElementById('tpre-language-query-button');
      var resultEl = document.getElementById('tpre-language-query-result');
      var data = window.TPRELanguageQuery || {};
      var nonce = data.nonce || (card ? card.getAttribute('data-nonce') : '');
      var ajaxUrl =
        data.ajaxUrl ||
        window.ajaxurl ||
        (card ? card.getAttribute('data-ajax-url') : '');
      var timer = null;
      var requestId = 0;

      if (
        !card ||
        !inputEl ||
        !engineEl ||
        !buttonEl ||
        !resultEl ||
        !ajaxUrl ||
        !nonce
      ) {
        return;
      }

      function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
      }

      function renderMessage(message, type) {
        var cls = type === 'error' ? 'notice notice-error' : 'notice notice-info';
        resultEl.innerHTML =
          '<div class="notice-inline ' +
          cls +
          '"><p>' +
          escapeHtml(message) +
          '</p></div>';
      }

      function getStatusLabel(item) {
        if (item.status === 'supported') {
          return (
            '<span class="tpre-language-query-supported">' +
            escapeHtml(__('支持', 'langrouter-for-translatepress')) +
            '</span>'
          );
        }
        if (item.status === 'unknown') {
          return (
            '<span class="tpre-language-query-unknown">' +
            escapeHtml(__('暂时无法校验', 'langrouter-for-translatepress')) +
            '</span>'
          );
        }
        return (
          '<span class="tpre-language-query-unsupported">' +
          escapeHtml(__('不支持', 'langrouter-for-translatepress')) +
          '</span>'
        );
      }

      function renderResult(payload) {
        var selected = payload.selected || {};
        var supportedBy = Array.isArray(payload.supported_by)
          ? payload.supported_by
          : [];
        var allResults = Array.isArray(payload.all_model_results)
          ? payload.all_model_results
          : [];
        var summaryParts = [];
        summaryParts.push(
          escapeHtml(__('输入：', 'langrouter-for-translatepress')) +
            '<code>' +
            escapeHtml(payload.raw_input || '') +
            '</code>',
        );
        if ((payload.normalized_input || '') !== (payload.raw_input || '')) {
          summaryParts.push(
            escapeHtml(__('识别：', 'langrouter-for-translatepress')) +
              '<code>' +
              escapeHtml(payload.normalized_input || '') +
              '</code>',
          );
        }

        var html =
          '<div class="tpre-language-query-summary"><p>' +
          summaryParts.join('&emsp;') +
          '</p>';
        var currentRow = selected.current_row || {};
        html +=
          '<p>' +
          escapeHtml(__('当前选择：', 'langrouter-for-translatepress')) +
          '<strong>' +
          escapeHtml(selected.label || '') +
          '</strong>';
        if (currentRow.model) {
          html +=
            escapeHtml(__('；当前配置模型：', 'langrouter-for-translatepress')) +
            '<code>' +
            escapeHtml(currentRow.model) +
            '</code>（' +
            (currentRow.enabled
              ? __('已启用', 'langrouter-for-translatepress')
              : __('未启用', 'langrouter-for-translatepress')) +
            '） → ' +
            getStatusLabel(currentRow);
        }
        html += '</p>';
        if (selected.message) {
          html +=
            '<p class="description">' + escapeHtml(selected.message) + '</p>';
        }
        var selectedSupported = Array.isArray(selected.supported_models)
          ? selected.supported_models
          : [];
        if (selectedSupported.length > 0) {
          html +=
            '<div><strong>' +
            escapeHtml(
              __('当前引擎下支持该语言的模型：', 'langrouter-for-translatepress'),
            ) +
            '</strong><div class="tpre-language-query-tags">';
          selectedSupported.forEach(function (item) {
            html +=
              '<span class="tpre-language-query-tag">' +
              escapeHtml(item.model || '') +
              (item.is_current
                ? ' <span class="tpre-language-query-current">' +
                  escapeHtml(__('当前', 'langrouter-for-translatepress')) +
                  '</span>'
                : '') +
              '</span>';
          });
          html += '</div></div>';
        } else {
          html +=
            '<p><strong>' +
            escapeHtml(
              __('当前引擎下支持该语言的模型：', 'langrouter-for-translatepress'),
            ) +
            '</strong>' +
            escapeHtml(__('未找到支持项。', 'langrouter-for-translatepress')) +
            '</p>';
        }
        html += '</div>';

        if (supportedBy.length > 0) {
          html +=
            '<div><strong>' +
            escapeHtml(
              wp.i18n.__('所有支持该语言的模型：', 'langrouter-for-translatepress')
            ) +
            '</strong><div class="tpre-language-query-tags">';
          supportedBy.forEach(function (item) {
            html +=
              '<span class="tpre-language-query-tag">' +
              escapeHtml(item.label || '') +
              ' / ' +
              escapeHtml(item.model || '') +
              '</span>';
          });
          html += '</div></div>';
        } else {
          html +=
            '<p><strong>' +
            escapeHtml(
              __('所有支持该语言的模型：', 'langrouter-for-translatepress'),
            ) +
            '</strong>' +
            escapeHtml(
              __(
                '当前这 4 个翻译引擎下未找到支持项。',
                'langrouter-for-translatepress',
              ),
            ) +
            '</p>';
        }

        html +=
          '<table class="tpre-language-query-table"><thead><tr><th>' +
          escapeHtml(__('引擎', 'langrouter-for-translatepress')) +
          '</th><th>' +
          escapeHtml(__('模型', 'langrouter-for-translatepress')) +
          '</th><th>' +
          escapeHtml(__('当前配置', 'langrouter-for-translatepress')) +
          '</th><th>' +
          escapeHtml(__('启用', 'langrouter-for-translatepress')) +
          '</th><th>' +
          escapeHtml(__('是否支持', 'langrouter-for-translatepress')) +
          '</th><th>' +
          escapeHtml(__('识别码', 'langrouter-for-translatepress')) +
          '</th></tr></thead><tbody>';
        allResults.forEach(function (item) {
          var candidates =
            Array.isArray(item.candidates) && item.candidates.length
              ? item.candidates.join(', ')
              : '-';
          html +=
            '<tr>' +
            '<td>' +
            escapeHtml(item.label || '') +
            '</td>' +
            '<td><code>' +
            escapeHtml(item.model || '') +
            '</code></td>' +
            '<td>' +
            (item.is_current
              ? '<span class="tpre-language-query-current">' +
                escapeHtml(__('当前', 'langrouter-for-translatepress')) +
                '</span>'
              : '-') +
            '</td>' +
            '<td>' +
            escapeHtml(
              item.enabled
                ? __('已启用', 'langrouter-for-translatepress')
                : __('未启用', 'langrouter-for-translatepress'),
            ) +
            '</td>' +
            '<td>' +
            getStatusLabel(item) +
            '</td>' +
            '<td><code>' +
            escapeHtml(candidates) +
            '</code></td>' +
            '</tr>';
        });
        html += '</tbody></table>';
        resultEl.innerHTML = html;
      }

      async function runQuery(event) {
        if (event && typeof event.preventDefault === 'function') {
          event.preventDefault();
        }
        if (event && typeof event.stopPropagation === 'function') {
          event.stopPropagation();
        }

        var language = inputEl.value.trim();
        var engine = engineEl.value;
        if (!language) {
          resultEl.innerHTML = '';
          return false;
        }

        var currentId = ++requestId;
        renderMessage(__('查询中…', 'langrouter-for-translatepress'), 'info');

        var body = new URLSearchParams();
        body.append('action', 'tpre_query_language_support');
        body.append('nonce', nonce);
        body.append('language', language);
        body.append('engine', engine);

        try {
          var response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: body.toString(),
            credentials: 'same-origin',
          });
          var text = await response.text();
          var json = null;
          try {
            json = JSON.parse(text);
          } catch (parseError) {
            throw new Error(
              'non-json:' + response.status + ':' + text.slice(0, 240),
            );
          }
          if (currentId !== requestId) {
            return false;
          }
          if (!json || !json.success) {
            var message =
              json && json.data && json.data.message
                ? json.data.message
                : __('查询失败，请稍后重试。', 'langrouter-for-translatepress');
            renderMessage(message, 'error');
            return false;
          }
          renderResult(json.data || {});
          return false;
        } catch (error) {
          if (currentId !== requestId) {
            return false;
          }
          renderMessage(
            __('查询失败：', 'langrouter-for-translatepress') +
              (error && error.message
                ? error.message
                : __(
                    '请检查后台 AJAX 是否正常。',
                    'langrouter-for-translatepress',
                  )),
            'error',
          );
          return false;
        }
      }

      function scheduleQuery() {
        window.clearTimeout(timer);
        timer = window.setTimeout(function () {
          runQuery();
        }, 420);
      }

      inputEl.addEventListener('input', scheduleQuery);
      inputEl.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
          window.clearTimeout(timer);
          runQuery(event);
          return false;
        }
      });
      engineEl.addEventListener('change', runQuery);
      buttonEl.addEventListener('click', runQuery);

      var form = inputEl.form;
      if (form) {
        form.addEventListener('submit', function (event) {
          if (
            document.activeElement === inputEl ||
            document.activeElement === buttonEl
          ) {
            event.preventDefault();
            event.stopPropagation();
            runQuery();
          }
        });
      }
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  })(window.wp || {});

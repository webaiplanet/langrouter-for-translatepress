(function () {
    function initMaskedFieldSelection(field) {
        if (!field) {
            return;
        }
        var masked = field.getAttribute('data-masked-value') || '';
        field.addEventListener('focus', function () {
            if (!field.dataset.userEdited && field.value === masked && masked !== '') {
                field.select();
            }
        });
        field.addEventListener('input', function () {
            field.dataset.userEdited = '1';
        });
    }

    function markMaskedFieldUnchangedOnSubmit(field) {
        if (!field) {
            return;
        }
        var form = field.form;
        var masked = field.getAttribute('data-masked-value') || '';
        var flagName = field.getAttribute('data-unchanged-flag-name') || '';
        if (!form || !flagName) {
            return;
        }
        form.addEventListener('submit', function () {
            var existing = form.querySelector('input[type="hidden"][name="' + flagName.replace(/"/g, '\"') + '"]');
            if (existing && existing.parentNode) {
                existing.parentNode.removeChild(existing);
            }
            if (!field.dataset.userEdited && masked !== '' && field.value === masked) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = flagName;
                hidden.value = '1';
                form.appendChild(hidden);
            }
        });
    }

    function initDeeplKeyPool() {
        var field = document.getElementById('tpre-deepl-keys-text');
        var gutter = document.getElementById('tpre-deepl-line-numbers');
        if (!field || !gutter) {
            return;
        }
        var masked = field.getAttribute('data-masked-value') || '';

        function getLineCount(value) {
            if (value === '') {
                return 1;
            }
            return value.split(/\r\n|\r|\n/).length;
        }

        function syncGutterMetrics() {
            var styles = window.getComputedStyle(field);
            gutter.style.height = field.offsetHeight + 'px';
            gutter.style.font = styles.font;
            gutter.style.lineHeight = styles.lineHeight;
            gutter.style.paddingTop = styles.paddingTop;
            gutter.style.paddingBottom = styles.paddingBottom;
        }

        function renderLineNumbers() {
            var count = Math.max(getLineCount(field.value), 10);
            var html = '';
            for (var i = 1; i <= count; i++) {
                html += '<div>' + i + '</div>';
            }
            gutter.innerHTML = html;
            gutter.scrollTop = field.scrollTop;
        }

        function syncAll() {
            syncGutterMetrics();
            renderLineNumbers();
        }

        field.addEventListener('focus', function () {
            if (!field.dataset.userEdited && field.value === masked && masked !== '') {
                field.select();
            }
        });
        field.addEventListener('input', function () {
            field.dataset.userEdited = '1';
            renderLineNumbers();
        });
        field.addEventListener('scroll', function () {
            gutter.scrollTop = field.scrollTop;
        });
        field.addEventListener('mouseup', syncAll);
        window.addEventListener('resize', syncAll);
        if (window.ResizeObserver) {
            new ResizeObserver(syncAll).observe(field);
        }
        markMaskedFieldUnchangedOnSubmit(field);
        syncAll();
    }

    function initVolcAccountsPool() {
        var field = document.getElementById('tpre-volc-accounts-raw');
        initMaskedFieldSelection(field);
        markMaskedFieldUnchangedOnSubmit(field);
    }



    function initOpenAICompatiblePreset() {
        var button = document.getElementById('tpre-openai-compatible-apply-safe-preset');
        if (!button) {
            return;
        }
        var preset = {
            'timeout': '60',
            'concurrency': '4',
            'max_tokens': '2200',
            'retry_count': '2',
            'short_text_merge_threshold': '36',
            'temperature': '0',
            'top_p': '1',
            'batch_size': '6',
            'batch_max_chars': '1200',
            'label_max_tokens': '0',
            'long_text_threshold': '1800',
            'long_text_chunk_chars': '1200',
            'long_html_chunk_chars': '1600',
            'long_text_medium_threshold': '1600',
            'long_text_concurrency_medium': '4',
            'long_text_large_threshold': '2400',
            'long_text_concurrency_large': '3',
            'long_text_extreme_threshold': '3200',
            'long_text_concurrency_extreme': '2',
            'single_request_timeout_base': '45',
            'single_request_timeout_step_chars': '700',
            'single_request_timeout_step_sec': '10',
            'single_request_timeout_html_bonus': '10',
            'single_request_timeout_cap': '180'
        };

        button.addEventListener('click', function () {
            Object.keys(preset).forEach(function (key) {
                var selector = 'input[name="tpre_settings[models][openai_compatible][' + key + ']"]';
                var field = document.querySelector(selector);
                if (field) {
                    field.value = preset[key];
                }
            });
            window.alert(button.getAttribute('data-success-message') || 'Preset applied. Remember to save settings.');
        });
    }

    function init() {
        initDeeplKeyPool();
        initVolcAccountsPool();
        initOpenAICompatiblePreset();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

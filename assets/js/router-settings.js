(function (wp) {
    const __ =
        wp && wp.i18n && wp.i18n.__
            ? wp.i18n.__
            : function (text) {
                return text;
            };

    const root = document.querySelector('.tpre-post-type-rules');
    if (!root) {
        return;
    }

    const body = root.querySelector('.tpre-post-type-rule-body');
    const addButton = root.querySelector('.tpre-add-rule');
    const emptyState = root.querySelector('.tpre-post-type-empty-state');
    const remainingText = root.querySelector('.tpre-post-type-remaining');
    const postTypes = JSON.parse(root.getAttribute('data-post-types') || '{}');
    const engines = JSON.parse(root.getAttribute('data-engines') || '{}');
    const labels = {
        emptyPostType: root.getAttribute('data-empty-post-type-label') || '',
        emptyEngine: root.getAttribute('data-empty-engine-label') || '',
        remove: root.getAttribute('data-remove-label') || '',
        fallbackNone: root.getAttribute('data-fallback-none-label') || __('失败即停止', 'langrouter-for-translatepress'),
        fallbackDefaultOnly: root.getAttribute('data-fallback-default-only-label') || __('仅默认引擎', 'langrouter-for-translatepress'),
        fallbackGlobalChain: root.getAttribute('data-fallback-global-chain-label') || __('全局链：语言分配 → 回退规则 → 默认引擎', 'langrouter-for-translatepress'),
        noMore: root.getAttribute('data-no-more-label') || ''
    };

    const serializedRulesInput = document.querySelector('.tpre-post-type-rules-json');

    function parseRowPostTypes(row) {
        const hidden = row.querySelector('.tpre-post-types-hidden');
        if (!hidden || !hidden.value) {
            return [];
        }
        try {
            const parsed = JSON.parse(hidden.value);
            return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
        } catch (error) {
            return [];
        }
    }

    function writeRowPostTypes(row, postTypeList) {
        const unique = [];
        postTypeList.forEach((slug) => {
            slug = String(slug || '').trim();
            if (!slug || unique.includes(slug)) {
                return;
            }
            unique.push(slug);
        });
        const hidden = row.querySelector('.tpre-post-types-hidden');
        if (hidden) {
            hidden.value = JSON.stringify(unique);
        }
    }

    function getAllSelectedPostTypes(exceptRow = null) {
        const selected = [];
        body.querySelectorAll('.tpre-post-type-rule-row').forEach((row) => {
            if (exceptRow && row === exceptRow) {
                return;
            }
            parseRowPostTypes(row).forEach((slug) => {
                if (!selected.includes(slug)) {
                    selected.push(slug);
                }
            });
        });
        return selected;
    }

    function refreshRow(row) {
        const current = parseRowPostTypes(row);
        const tagsWrap = row.querySelector('.tpre-post-type-tags');
        const addSelect = row.querySelector('.tpre-post-type-add-select');
        const fallbackModeSelect = row.querySelector('.tpre-fallback-mode-select');

        if (tagsWrap) {
            tagsWrap.innerHTML = '';
            current.forEach((slug) => {
                const meta = postTypes[slug] || {};
                const label = meta.label ? `${meta.label} (${slug})` : slug;
                const tag = document.createElement('span');
                tag.className = 'tpre-post-type-tag';
                tag.innerHTML = `<span>${label}</span>`;
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'tpre-post-type-tag-remove';
                removeButton.setAttribute('data-post-type', slug);
                removeButton.setAttribute('aria-label', labels.remove);
                removeButton.textContent = '×';
                tag.appendChild(removeButton);
                tagsWrap.appendChild(tag);
            });
        }

        if (addSelect) {
            const usedByOthers = new Set(getAllSelectedPostTypes(row));
            addSelect.innerHTML = '';

            const available = Object.keys(postTypes).filter((slug) => !current.includes(slug) && !usedByOthers.has(slug));

            if (available.length > 0) {
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = labels.emptyPostType;
                placeholder.disabled = true;
                placeholder.selected = true;
                placeholder.hidden = true;
                addSelect.appendChild(placeholder);

                available.forEach((slug) => {
                    const item = postTypes[slug] || {};
                    const label = item.label ? `${item.label} (${slug})` : slug;
                    const option = document.createElement('option');
                    option.value = slug;
                    option.textContent = label;
                    addSelect.appendChild(option);
                });

                addSelect.disabled = false;
            } else {
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = labels.noMore;
                emptyOption.disabled = true;
                emptyOption.selected = true;
                addSelect.appendChild(emptyOption);
                addSelect.disabled = true;
            }
        }

        if (fallbackModeSelect && !fallbackModeSelect.value) {
            fallbackModeSelect.value = 'default_only';
        }
    }


    function collectRules() {
        const rules = [];
        body.querySelectorAll('.tpre-post-type-rule-row').forEach((row) => {
            const postTypes = parseRowPostTypes(row);
            const engineSelect = row.querySelector('.tpre-engine-select');
            const fallbackModeSelect = row.querySelector('.tpre-fallback-mode-select');
            rules.push({
                post_types: postTypes,
                engine: engineSelect ? String(engineSelect.value || '').trim() : '',
                fallback_mode: fallbackModeSelect ? String(fallbackModeSelect.value || 'default_only').trim() : 'default_only'
            });
        });
        return rules;
    }

    function syncSerializedRules() {
        if (!serializedRulesInput) {
            return;
        }
        serializedRulesInput.value = JSON.stringify(collectRules());
    }

    function updateAvailability() {
        body.querySelectorAll('.tpre-post-type-rule-row').forEach((row) => refreshRow(row));
        const usedCount = getAllSelectedPostTypes().length;
        const totalCount = Object.keys(postTypes).length;
        const remainingCount = Math.max(0, totalCount - usedCount);

        if (remainingText) {
            remainingText.textContent = remainingCount > 0 ? '' : labels.noMore;
        }
        if (addButton) {
            addButton.disabled = remainingCount <= 0;
        }
        if (emptyState) {
            emptyState.style.display = body.querySelectorAll('.tpre-post-type-rule-row').length > 0 ? 'none' : '';
        }
        syncSerializedRules();
    }

    function createFallbackModeSelect(selectedValue = 'default_only') {
        const select = document.createElement('select');
        select.className = 'tpre-fallback-mode-select';
        select.name = 'trp_machine_translation_settings[tpre_post_type_rule_fallback_mode][]';

        [
            ['default_only', labels.fallbackDefaultOnly],
            ['global_chain', labels.fallbackGlobalChain],
            ['none', labels.fallbackNone]
        ].forEach(([value, label]) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            if ((selectedValue || 'default_only') === value) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        return select;
    }

    function createEngineSelect(selectedValue = '') {
        const select = document.createElement('select');
        select.className = 'tpre-engine-select';
        select.name = 'trp_machine_translation_settings[tpre_post_type_rule_engine][]';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = labels.emptyEngine;
        select.appendChild(placeholder);
        Object.keys(engines).forEach((slug) => {
            const option = document.createElement('option');
            option.value = slug;
            option.textContent = engines[slug];
            if (selectedValue === slug) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        return select;
    }

    function createRow(rule = null) {
        const row = document.createElement('tr');
        row.className = 'tpre-post-type-rule-row';

        const postTypeCell = document.createElement('td');
        const picker = document.createElement('div');
        picker.className = 'tpre-post-type-picker';
        const tags = document.createElement('div');
        tags.className = 'tpre-post-type-tags';
        const addSelect = document.createElement('select');
        addSelect.className = 'tpre-post-type-add-select';
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.className = 'tpre-post-types-hidden';
        hidden.name = 'trp_machine_translation_settings[tpre_post_type_rule_post_types][]';
        hidden.value = JSON.stringify(rule && Array.isArray(rule.post_types) ? rule.post_types : []);
        picker.appendChild(tags);
        picker.appendChild(addSelect);
        picker.appendChild(hidden);
        postTypeCell.appendChild(picker);

        const engineCell = document.createElement('td');
        engineCell.appendChild(createEngineSelect(rule && rule.engine ? rule.engine : ''));

        const chainCell = document.createElement('td');
        chainCell.className = 'tpre-post-type-chain-cell';
        chainCell.appendChild(createFallbackModeSelect(rule && rule.fallback_mode ? rule.fallback_mode : 'default_only'));

        const actionCell = document.createElement('td');
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'button-link-delete tpre-remove-rule';
        removeButton.textContent = labels.remove;
        actionCell.appendChild(removeButton);

        row.appendChild(postTypeCell);
        row.appendChild(engineCell);
        row.appendChild(chainCell);
        row.appendChild(actionCell);
        body.appendChild(row);
        refreshRow(row);
    }

    body.querySelectorAll('.tpre-post-type-rule-row').forEach((row) => refreshRow(row));

    body.addEventListener('change', function (event) {
        if (event.target && event.target.classList.contains('tpre-post-type-add-select')) {
            const row = event.target.closest('.tpre-post-type-rule-row');
            const current = parseRowPostTypes(row);
            if (event.target.value && !current.includes(event.target.value)) {
                current.push(event.target.value);
                writeRowPostTypes(row, current);
            }
            event.target.value = '';
            updateAvailability();
            return;
        }

        if (event.target && (event.target.classList.contains('tpre-engine-select') || event.target.classList.contains('tpre-fallback-mode-select'))) {
            syncSerializedRules();
        }

    });

    body.addEventListener('click', function (event) {
        const removeRuleButton = event.target.closest('.tpre-remove-rule');
        if (removeRuleButton) {
            const row = removeRuleButton.closest('.tpre-post-type-rule-row');
            if (row) {
                row.remove();
                updateAvailability();
            }
            return;
        }

        const removeTagButton = event.target.closest('.tpre-post-type-tag-remove');
        if (removeTagButton) {
            const row = removeTagButton.closest('.tpre-post-type-rule-row');
            const current = parseRowPostTypes(row).filter((slug) => slug !== removeTagButton.getAttribute('data-post-type'));
            writeRowPostTypes(row, current);
            updateAvailability();
        }
    });

    if (addButton) {
        addButton.addEventListener('click', function () {
            createRow();
            updateAvailability();
        });
    }

    updateAvailability();
})();

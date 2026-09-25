(() => {
    const root = document.querySelector('[data-vf-ui-kit-nav]');
    if (!root) {
        return;
    }

    const toggles = [...root.querySelectorAll('[data-vf-ui-kit-nav-toggle]')];
    let lastOpenedToggle = null;
    const panelFor = (toggle) => document.getElementById(toggle.getAttribute('aria-controls'));
    const resetCompanySearch = () => {
        const search = root.querySelector('[data-vf-ui-kit-company-search]');
        if (!search) {
            return;
        }
        search.value = '';
        root.querySelectorAll('[data-vf-ui-kit-company-option]').forEach((option) => {
            option.hidden = false;
        });
        root.querySelector('[data-vf-ui-kit-company-empty]').textContent = '';
    };
    const close = (toggle, restoreFocus = false) => {
        const panel = panelFor(toggle);
        if (!panel) {
            return;
        }
        toggle.setAttribute('aria-expanded', 'false');
        panel.hidden = true;
        if (toggle.getAttribute('aria-controls') === 'vf-ui-nav-crumbs') {
            toggle.textContent = '…';
        }
        if (toggle.getAttribute('aria-controls') === 'vf-ui-nav-company') {
            resetCompanySearch();
        }
        if (restoreFocus) {
            toggle.focus();
        }
    };
    const closeAll = () => toggles.forEach((toggle) => close(toggle));

    toggles.forEach((toggle) => {
        const panel = panelFor(toggle);
        if (!panel) {
            return;
        }
        toggle.addEventListener('click', () => {
            const opening = panel.hidden;
            toggles.forEach((other) => {
                if (other !== toggle && toggle.dataset.vfUiKitNavGroup
                    && other.dataset.vfUiKitNavGroup === toggle.dataset.vfUiKitNavGroup) {
                    close(other);
                }
            });
            panel.hidden = !opening;
            toggle.setAttribute('aria-expanded', String(opening));
            if (opening) {
                lastOpenedToggle = toggle;
            }
            if (toggle.getAttribute('aria-controls') === 'vf-ui-nav-crumbs') {
                toggle.textContent = opening ? '×' : '…';
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        const openToggle = toggles.find((toggle) => toggle.getAttribute('aria-expanded') === 'true'
            && (toggle === document.activeElement || panelFor(toggle)?.contains(document.activeElement)))
            || (lastOpenedToggle?.getAttribute('aria-expanded') === 'true' ? lastOpenedToggle : null);
        if (openToggle) {
            event.preventDefault();
            close(openToggle, true);
        }
    });
    document.addEventListener('pointerdown', (event) => {
        if (!toggles.some((toggle) => toggle.getAttribute('aria-expanded') === 'true')) {
            return;
        }
        const inside = toggles.some((toggle) => toggle.contains(event.target) || panelFor(toggle)?.contains(event.target));
        if (!inside) {
            closeAll();
        }
    });
    document.addEventListener('focusin', (event) => {
        const inside = toggles.some((toggle) => toggle.contains(event.target) || panelFor(toggle)?.contains(event.target));
        if (!inside) {
            closeAll();
        }
    });

    const search = root.querySelector('[data-vf-ui-kit-company-search]');
    const options = [...root.querySelectorAll('[data-vf-ui-kit-company-option]')];
    const empty = root.querySelector('[data-vf-ui-kit-company-empty]');
    if (search && empty) {
        const normalizeName = (value) => value.toLocaleLowerCase('ru').replace(/\s+/g, ' ').trim();
        const normalizeNumber = (value) => value.replace(/\D/g, '');
        search.addEventListener('input', () => {
            const query = search.value.trim();
            const nameQuery = normalizeName(query);
            const numberQuery = normalizeNumber(query);
            const numberSearch = /^инн(?=\s|:|\d|$)/iu.test(query) || /^[\d\s:-]+$/.test(query);
            let visible = 0;
            options.forEach((option) => {
                option.hidden = query !== ''
                    && !(numberSearch && numberQuery === '')
                    && !normalizeName(option.dataset.name).includes(nameQuery)
                    && !(numberSearch && numberQuery !== '' && normalizeNumber(option.dataset.number).includes(numberQuery));
                visible += Number(!option.hidden);
            });
            empty.textContent = visible === 0 ? 'Совпадений нет.' : '';
        });
    }
    options.forEach((option) => {
        option.addEventListener('click', () => {
            options.forEach((item) => item.setAttribute('aria-pressed', String(item === option)));
            root.querySelector('[data-vf-ui-kit-company-initial]').textContent = option.dataset.initial;
            root.querySelector('[data-vf-ui-kit-company-name]').textContent = option.dataset.name;
            root.querySelector('[data-vf-ui-kit-company-number]').textContent = 'ИНН ' + option.dataset.number;
            const trigger = root.querySelector('[aria-controls="vf-ui-nav-company"]');
            close(trigger, true);
        });
    });
})();

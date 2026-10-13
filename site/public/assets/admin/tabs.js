// Вкладки формы в админке (WAI-ARIA Tabs). Без скрипта разделы формы идут подряд.
// Скрипт только переключает атрибуты hidden и aria-selected; оформление -- классами в Twig.
(() => {
    const initializeTabs = (tablist) => {
        const tabs = [...tablist.querySelectorAll('[role="tab"]')];
        const panels = tabs.map((tab) => document.getElementById(tab.getAttribute('aria-controls')));

        if (tabs.length === 0 || panels.some((panel) => !(panel instanceof HTMLElement))) {
            return;
        }

        const select = (index, moveFocus) => {
            tabs.forEach((tab, tabIndex) => {
                const selected = tabIndex === index;
                tab.setAttribute('aria-selected', String(selected));
                tab.tabIndex = selected ? 0 : -1;
                panels[tabIndex].hidden = !selected;
            });

            if (moveFocus) {
                tabs[index].focus();
            }
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => select(index, false));
            tab.addEventListener('keydown', (event) => {
                const last = tabs.length - 1;
                const target = {
                    ArrowRight: index === last ? 0 : index + 1,
                    ArrowLeft: index === 0 ? last : index - 1,
                    Home: 0,
                    End: last,
                }[event.key];

                if (target !== undefined) {
                    event.preventDefault();
                    select(target, true);
                }
            });
        });

        panels.forEach((panel) => {
            panel.tabIndex = 0;
            panel.querySelector('[data-admin-tab-heading]')?.setAttribute('hidden', '');
        });

        // После отправки с ошибками открываем первую вкладку с ошибкой, иначе -- первую.
        const invalid = tabs.findIndex((tab) => tab.hasAttribute('data-admin-tab-invalid'));
        select(invalid === -1 ? 0 : invalid, false);
        tablist.hidden = false;
    };

    document.querySelectorAll('[data-admin-tabs]').forEach(initializeTabs);
})();

// Вкладки секций «Что входит» и «Преимущества» (components/_tab_list.html.twig).
// Без JS список вкладок скрыт и видны все панели. Скрипт показывает список и кладёт панели
// в одну ячейку сетки: высота блока равна самой высокой панели, переключение не сдвигает страницу.
// Меняются только атрибуты и классы, которые есть в этом файле: col-start-1 row-start-1 invisible.
(() => {
    const STACK_CLASSES = ['col-start-1', 'row-start-1'];

    document.querySelectorAll('[data-vf-tabs]').forEach((root) => {
        const list = root.querySelector('[data-vf-tablist]');
        const tabs = list ? Array.from(list.querySelectorAll('[role="tab"]')) : [];
        const panels = tabs.map((tab) => document.getElementById(tab.getAttribute('aria-controls')));

        if (tabs.length === 0 || panels.some((panel) => panel === null)) {
            return;
        }

        const select = (index, moveFocus) => {
            tabs.forEach((tab, tabIndex) => {
                const active = tabIndex === index;
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.tabIndex = active ? 0 : -1;
                panels[tabIndex].classList.toggle('invisible', !active);
                panels[tabIndex].inert = !active;
                if (active && moveFocus) {
                    tab.focus();
                }
            });
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => select(index, false));
            tab.addEventListener('keydown', (event) => {
                const last = tabs.length - 1;
                const keys = {
                    ArrowRight: index === last ? 0 : index + 1,
                    ArrowDown: index === last ? 0 : index + 1,
                    ArrowLeft: index === 0 ? last : index - 1,
                    ArrowUp: index === 0 ? last : index - 1,
                    Home: 0,
                    End: last,
                };
                if (event.key in keys) {
                    event.preventDefault();
                    select(keys[event.key], true);
                }
            });
        });

        panels.forEach((panel) => panel.classList.add(...STACK_CLASSES));
        list.hidden = false;
        select(0, false);
    });
})();

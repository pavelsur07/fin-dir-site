// Выпадающие меню строк (.row-menu) -- нативный popover: открытие, Esc и клик вне
// делает браузер. Скрипт только ставит меню у кнопки: вниз или вверх, где больше места.
// Без скрипта меню открывается по центру экрана и остаётся рабочим.
document.addEventListener('beforetoggle', (event) => {
    const menu = event.target;
    if (!(menu instanceof HTMLElement) || !menu.matches('.row-menu') || event.newState !== 'open') {
        return;
    }
    const toggle = document.querySelector(`[popovertarget="${menu.id}"]`);
    if (!toggle) {
        return;
    }
    const rect = toggle.getBoundingClientRect();
    const viewport = document.documentElement;
    const spaceBelow = viewport.clientHeight - rect.bottom;
    const openUp = rect.top > spaceBelow;
    Object.assign(menu.style, {
        inset: 'auto',
        margin: '0',
        right: `${Math.max(8, viewport.clientWidth - rect.right)}px`,
        top: openUp ? 'auto' : `${rect.bottom + 4}px`,
        bottom: openUp ? `${viewport.clientHeight - rect.top + 4}px` : 'auto',
        // На низком экране меню прокручивается внутри, а не уходит за край.
        maxHeight: `${Math.max(rect.top, spaceBelow) - 12}px`,
    });
}, true);

// Меню в top layer и не едет вместе со страницей: при прокрутке закрываем
// (кроме прокрутки внутри самого меню).
const closeOpenMenu = (event) => {
    const menu = document.querySelector('.row-menu:popover-open');
    if (menu && !(event.target instanceof Node && menu.contains(event.target))) {
        menu.hidePopover();
    }
};
window.addEventListener('scroll', closeOpenMenu, true);
window.addEventListener('resize', closeOpenMenu);

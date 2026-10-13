// Progressive enhancement: нативный details остаётся запасным вариантом без JS/API.
(() => {
    if (!('popover' in HTMLElement.prototype)) return;

    const menus = [];
    document.querySelectorAll('[data-admin-actions]').forEach((details) => {
        const summary = details.querySelector('[data-admin-menu-toggle]');
        const menu = details.querySelector('[data-admin-row-menu]');
        if (!summary || !menu) return;

        const wrapper = document.createElement('div');
        const button = document.createElement('button');
        for (const attribute of summary.attributes) {
            button.setAttribute(attribute.name, attribute.value);
        }
        button.type = 'button';
        button.textContent = summary.textContent;
        button.setAttribute('popovertarget', menu.id);
        menu.setAttribute('popover', 'auto');
        wrapper.append(button, menu);
        details.replaceWith(wrapper);
        menus.push(menu);

        const position = () => {
            if (!menu.matches(':popover-open')) return;
            const anchor = button.getBoundingClientRect();
            const box = menu.getBoundingClientRect();
            const margin = 16;
            const gap = 8;
            const left = Math.max(margin, Math.min(anchor.right - box.width, innerWidth - box.width - margin));
            const below = anchor.bottom + gap;
            const above = anchor.top - box.height - gap;
            const top = below + box.height <= innerHeight - margin ? below : Math.max(margin, above);
            // Только геометрия popover, оформление полностью остаётся в CSS.
            menu.style.left = `${left}px`;
            menu.style.top = `${Math.min(top, Math.max(margin, innerHeight - box.height - margin))}px`;
        };
        menu.addEventListener('beforetoggle', (event) => {
            if (event.newState === 'open') requestAnimationFrame(position);
        });
        menu.addEventListener('toggle', position);
    });

    const dismiss = (event) => {
        // Внутренняя прокрутка длинного меню не должна закрывать его.
        for (const menu of menus) {
            if (menu.matches(':popover-open') && !(event.target instanceof Node && menu.contains(event.target))) {
                menu.hidePopover();
            }
        }
    };
    document.addEventListener('scroll', dismiss, {capture: true, passive: true});
    window.addEventListener('resize', dismiss);
    window.visualViewport?.addEventListener('resize', dismiss);
    document.querySelector('[data-admin-mobile-menu]')?.addEventListener('toggle', dismiss);
})();

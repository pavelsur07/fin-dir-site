(() => {
    // Заголовок после перехода по якорю встаёт на 88px: scroll-pt-16 у html (шапка) + scroll-mt-6 у H2; +8px запас на дробные пиксели.
    const HEADER_OFFSET = 96;

    const ACTIVE_LINK = ['font-semibold', 'text-fg'];
    const INACTIVE_LINK = ['font-medium', 'text-fg-muted'];
    const ACTIVE_DOT = 'bg-accent-fill';
    const INACTIVE_DOT = 'bg-border';

    const initializeArticleToc = () => {
        const body = document.querySelector('[data-vf-article-body]');
        const links = [...document.querySelectorAll('.js-toc-link')];

        if (!(body instanceof HTMLElement) || links.length === 0 || !('IntersectionObserver' in window)) {
            return;
        }

        // Заголовки, у которых есть пункт оглавления (в списке не больше 7), в порядке документа.
        const ids = [...new Set(links.map((link) => link.getAttribute('href').slice(1)))];
        const headings = ids
            .map((id) => document.getElementById(id))
            .filter((heading) => heading instanceof HTMLElement && body.contains(heading))
            .sort((first, second) => (first.compareDocumentPosition(second) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1));

        if (headings.length === 0) {
            return;
        }

        const progress = [...document.querySelectorAll('.js-toc-progress')];
        let activeId = null;

        const render = () => {
            links.forEach((link) => {
                const active = link.getAttribute('href') === `#${activeId}`;
                const dot = link.querySelector('.js-toc-dot');

                link.classList.remove(...(active ? INACTIVE_LINK : ACTIVE_LINK));
                link.classList.add(...(active ? ACTIVE_LINK : INACTIVE_LINK));

                if (active) {
                    link.setAttribute('aria-current', 'true');
                } else {
                    link.removeAttribute('aria-current');
                }

                if (dot) {
                    dot.classList.remove(active ? INACTIVE_DOT : ACTIVE_DOT);
                    dot.classList.add(active ? ACTIVE_DOT : INACTIVE_DOT);
                }
            });

            const index = headings.findIndex((heading) => heading.id === activeId);
            progress.forEach((element) => {
                element.textContent = index === -1 ? '' : `${index + 1} из ${headings.length}`;
            });
        };

        // Активен последний заголовок, который дошёл до линии под шапкой.
        const update = () => {
            let current = null;

            headings.forEach((heading) => {
                if (heading.getBoundingClientRect().top <= HEADER_OFFSET) {
                    current = heading.id;
                }
            });

            if (current !== activeId) {
                activeId = current;
                render();
            }
        };

        // Полоса от линии под шапкой вниз: заголовок пересекает её верхнюю границу при каждой смене активного.
        const observer = new IntersectionObserver(update, {
            rootMargin: `-${HEADER_OFFSET}px 0px -60% 0px`,
        });

        headings.forEach((heading) => observer.observe(heading));
        update();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeArticleToc);
    } else {
        initializeArticleToc();
    }
})();

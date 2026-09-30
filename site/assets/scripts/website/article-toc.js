(() => {
    // Заголовок после перехода по якорю встаёт на 88px: scroll-pt-16 у html (шапка) + scroll-mt-6 у H2; +8px запас на дробные пиксели.
    const HEADER_OFFSET = 96;

    const ACTIVE_LINK = ['font-semibold', 'text-fg'];
    const INACTIVE_LINK = ['font-medium', 'text-fg-muted'];
    const ACTIVE_DOT = 'bg-accent-fill';
    const INACTIVE_DOT = 'bg-border';
    const ACTIVE_SEGMENT = 'bg-accent-fill';
    const INACTIVE_SEGMENT = 'bg-border-subtle';

    const initializeArticleToc = () => {
        const body = document.querySelector('[data-vf-article-body]');
        const links = [...document.querySelectorAll('.js-toc-link')];

        if (!(body instanceof HTMLElement) || links.length === 0 || !('IntersectionObserver' in window)) {
            return;
        }

        // Все H2 статьи в порядке документа: в оглавлении их не больше 7, но счётчик считает все.
        const headings = [...body.querySelectorAll('h2[id]')];

        if (headings.length === 0) {
            return;
        }

        const progress = [...document.querySelectorAll('.js-toc-progress')];
        const segments = [...document.querySelectorAll('.js-toc-segment')];
        const end = document.querySelector('.js-toc-end');
        let activeIndex = -1;
        let atEnd = false;

        const render = () => {
            const activeId = activeIndex === -1 ? null : headings[activeIndex].id;

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

            // Пройденные разделы и текущий закрашены, остальные нет.
            segments.forEach((segment, index) => {
                const passed = index <= activeIndex;

                segment.classList.remove(passed ? INACTIVE_SEGMENT : ACTIVE_SEGMENT);
                segment.classList.add(passed ? ACTIVE_SEGMENT : INACTIVE_SEGMENT);
            });

            progress.forEach((element) => {
                element.textContent = `${activeIndex + 1} из ${headings.length}`;
            });
        };

        // Активен последний заголовок, дошедший до линии под шапкой; у конца текста -- последний H2.
        const update = () => {
            let current = -1;

            // scrollY > 0: у короткой статьи конец виден сразу, но до прокрутки активного раздела ещё нет.
            if (atEnd && window.scrollY > 0) {
                current = headings.length - 1;
            } else {
                headings.forEach((heading, index) => {
                    if (heading.getBoundingClientRect().top <= HEADER_OFFSET) {
                        current = index;
                    }
                });
            }

            if (current !== activeIndex) {
                activeIndex = current;
                render();
            }
        };

        // Полоса от линии под шапкой вниз: заголовок пересекает её верхнюю границу при каждой смене активного.
        const headingObserver = new IntersectionObserver(update, {
            rootMargin: `-${HEADER_OFFSET}px 0px -60% 0px`,
        });

        headings.forEach((heading) => headingObserver.observe(heading));

        // Короткий последний раздел не дойдёт до линии: когда виден конец текста, он активен.
        if (end instanceof HTMLElement) {
            new IntersectionObserver((entries) => {
                atEnd = entries.some((entry) => entry.isIntersecting);
                update();
            }).observe(end);
        }

        update();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeArticleToc);
    } else {
        initializeArticleToc();
    }
})();

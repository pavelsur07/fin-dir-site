// Окно записи на разбор (sections/_lead_modal, docs/plan/Stage_Lead_Modal.md).
// Отправку формы ведёт navigation.js; здесь -- открытие, закрытие, правила автопоказа и память в localStorage.
// JS меняет только атрибуты (hidden) и класс блокировки прокрутки на <html>, CSS-свойства не задаёт.
(() => {
    const dialog = document.querySelector('[data-vf-lead-modal]');

    if (!(dialog instanceof HTMLDialogElement) || typeof dialog.showModal !== 'function') {
        return;
    }

    const storageKey = 'vf-lead-modal';
    const repeatAfterMs = 7 * 24 * 60 * 60 * 1000;
    const scrollShare = 0.55;
    const fallbackSeconds = 45;
    // Класс из темы; строка есть в исходнике, который сканирует Tailwind.
    const scrollLockClass = 'overflow-hidden';

    const form = dialog.querySelector('[data-vf-lead-form]');
    const fields = dialog.querySelector('[data-vf-lead-modal-fields]');
    const success = dialog.querySelector('[data-vf-lead-success]');
    const nameInput = dialog.querySelector('input[name="name"]');
    let opener = null;

    const trackGoal = (goalName) => {
        const ymId = window.VF_ANALYTICS && window.VF_ANALYTICS.ymCounterId;

        if (typeof window.ym === 'function' && ymId && ymId !== 'YM_COUNTER_ID') {
            window.ym(ymId, 'reachGoal', goalName);
        }
    };

    // {shownAt, submittedAt} в мс. null -- хранилище недоступно: автоматически окно не показываем.
    const readState = () => {
        try {
            const state = JSON.parse(window.localStorage.getItem(storageKey));

            return state && typeof state === 'object' ? state : {};
        } catch (error) {
            return null;
        }
    };

    const writeState = (patch) => {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify({ ...(readState() || {}), ...patch }));
        } catch (error) {
            // Без хранилища окно просто не запоминает показ.
        }
    };

    const autoShowAllowed = () => {
        const state = readState();

        return state !== null
            && !state.submittedAt
            && !(typeof state.shownAt === 'number' && Date.now() - state.shownAt < repeatAfterMs);
    };

    const open = (source, trigger = null) => {
        if (dialog.open) {
            return;
        }

        opener = trigger;
        fields.hidden = false;
        success.hidden = true;
        document.documentElement.classList.add(scrollLockClass);
        dialog.showModal();
        nameInput.focus({ preventScroll: true });
        writeState({ shownAt: Date.now() });
        trackGoal(source === 'auto' ? 'lead_modal_open_auto' : 'lead_modal_open_button');
    };

    dialog.addEventListener('close', () => {
        document.documentElement.classList.remove(scrollLockClass);

        if (opener && opener.isConnected) {
            opener.focus({ preventScroll: true });
        }
        opener = null;
    });

    dialog.addEventListener('click', (event) => {
        // Клик по затемнению: target -- сам dialog, а не содержимое карточки.
        if (event.target === dialog || event.target.closest('[data-vf-lead-modal-close]')) {
            dialog.close();
        }
    });

    document.addEventListener('vf:lead-submitted', (event) => {
        writeState({ submittedAt: Date.now() });

        if (event.target === form) {
            trackGoal('lead_modal_submit');
            fields.hidden = true;
            success.querySelector('[data-vf-lead-modal-close]').focus();
        }
    });

    // Кнопки-ссылки на #lead-form: без JS ведут к форме, с окном -- открывают его.
    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element ? event.target.closest('[data-vf-lead-modal-open]') : null;

        if (trigger && !dialog.open) {
            event.preventDefault();
            open('button', trigger);
        }
    });

    // ?modal=lead -- ссылка из рассылки или рекламы: окно сразу, параметр убираем, utm остаются.
    const url = new URL(window.location.href);
    if (url.searchParams.get('modal') === 'lead') {
        url.searchParams.delete('modal');
        window.history.replaceState(window.history.state, '', url.pathname + url.search + url.hash);
        open('button');
    }

    if (!autoShowAllowed()) {
        return;
    }

    // Форма заявки внизу страницы на экране -- окно поверх неё не нужно.
    const leadFormSection = document.getElementById('lead-form');
    const leadFormVisible = () => {
        if (!leadFormSection) {
            return false;
        }
        const rect = leadFormSection.getBoundingClientRect();

        return rect.top < window.innerHeight && rect.bottom > 0;
    };

    const cookieNotice = document.getElementById('cookieNotice');
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    let visibleSeconds = 0;
    let timer = 0;

    const disarm = () => {
        window.removeEventListener('scroll', onScroll);
        document.removeEventListener('mouseout', onMouseOut);
        window.clearInterval(timer);
    };

    const tryAutoShow = () => {
        const busy = dialog.open
            || document.querySelector('dialog[open]') !== null
            || (cookieNotice && !cookieNotice.hidden)
            || (document.activeElement instanceof Element && document.activeElement.matches('input, textarea, select'))
            || leadFormVisible()
            || !autoShowAllowed();

        if (busy) {
            // Отправил заявку или окно уже показано в другой вкладке -- правила больше не нужны.
            if (!autoShowAllowed()) {
                disarm();
            }

            return;
        }

        disarm();
        open('auto');
    };

    const onScroll = () => {
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;

        if (scrollable > 0 && window.scrollY / scrollable >= scrollShare) {
            tryAutoShow();
        }
    };

    // Уход курсора за верхний край окна браузера: только мышь.
    const onMouseOut = (event) => {
        if (event.relatedTarget === null && event.clientY <= 0) {
            tryAutoShow();
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    if (finePointer) {
        document.addEventListener('mouseout', onMouseOut);
    }
    // Запасной таймер считает только время, пока вкладка видна; занятость проверяется каждую секунду после порога.
    timer = window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            visibleSeconds += 1;
        }
        if (visibleSeconds >= fallbackSeconds) {
            tryAutoShow();
        }
    }, 1000);
})();

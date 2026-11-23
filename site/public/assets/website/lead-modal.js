// Окно записи на разбор (sections/_lead_modal, docs/plan/Stage_Lead_Modal.md).
// Отправку формы ведёт navigation.js; здесь -- открытие, закрытие, правила автопоказа и память в localStorage.
// JS меняет только атрибуты (hidden) и класс блокировки прокрутки на <html>, CSS-свойства не задаёт.
(() => {
    const dialog = document.querySelector('[data-vf-lead-modal]');

    if (!(dialog instanceof HTMLDialogElement) || typeof dialog.showModal !== 'function') {
        return;
    }

    const storageKey = 'vf-lead-modal';
    const dayMs = 24 * 60 * 60 * 1000;
    // Пауза автопоказа: 7 дней после показа или первого отказа, 30 дней после двух и более отказов.
    const repeatAfterMs = 7 * dayMs;
    const repeatAfterDismissalsMs = 30 * dayMs;
    const scrollShare = 0.55;
    const fallbackSeconds = 45;
    // Класс из темы; строка есть в исходнике, который сканирует Tailwind.
    const scrollLockClass = 'overflow-hidden';

    const form = dialog.querySelector('[data-vf-lead-form]');
    const fields = dialog.querySelector('[data-vf-lead-modal-fields]');
    const success = dialog.querySelector('[data-vf-lead-success]');
    const nameInput = dialog.querySelector('input[name="name"]');
    // Мышь и точный указатель: на сенсорных экранах автопоказ не ставит фокус в поле, чтобы не поднимать клавиатуру.
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    let opener = null;
    let submittedThisOpen = false;

    const trackGoal = (goalName, params) => {
        const ymId = window.VF_ANALYTICS && window.VF_ANALYTICS.ymCounterId;

        if (typeof window.ym === 'function' && ymId && ymId !== 'YM_COUNTER_ID') {
            if (params) {
                window.ym(ymId, 'reachGoal', goalName, params);
            } else {
                window.ym(ymId, 'reachGoal', goalName);
            }
        }
    };

    // {shownAt, submittedAt, dismissedAt, dismissCount}, время в мс. null -- хранилище недоступно: автоматически окно не показываем.
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

        if (state === null || state.submittedAt) {
            return false;
        }

        const dismissCount = typeof state.dismissCount === 'number' ? state.dismissCount : 0;
        const since = dismissCount > 0 ? state.dismissedAt : state.shownAt;
        const pause = dismissCount >= 2 ? repeatAfterDismissalsMs : repeatAfterMs;

        return !(typeof since === 'number' && Date.now() - since < pause);
    };

    const open = (source, trigger = null, params = undefined) => {
        if (dialog.open) {
            return;
        }

        opener = trigger;
        submittedThisOpen = false;
        fields.hidden = false;
        success.hidden = true;
        document.documentElement.classList.add(scrollLockClass);
        dialog.showModal();
        if (source === 'auto' && !finePointer) {
            dialog.focus({ preventScroll: true });
        } else {
            nameInput.focus({ preventScroll: true });
        }
        writeState({ shownAt: Date.now() });
        trackGoal(source === 'auto' ? 'lead_modal_open_auto' : 'lead_modal_open_button', params);
    };

    dialog.addEventListener('close', () => {
        document.documentElement.classList.remove(scrollLockClass);

        // Закрыл без заявки -- отказ: удлиняет паузу автопоказа.
        if (!submittedThisOpen) {
            const state = readState() || {};
            writeState({ dismissedAt: Date.now(), dismissCount: (typeof state.dismissCount === 'number' ? state.dismissCount : 0) + 1 });
            trackGoal('lead_modal_close');
        }

        // Кнопка могла остаться в закрытом меню шапки (панель «Услуги», мобильная панель):
        // тогда фокус -- на кнопку, которая это меню открывает.
        let focusTarget = opener && opener.isConnected ? opener : null;
        const hiddenHost = focusTarget && focusTarget.getClientRects().length === 0 ? focusTarget.closest('[hidden], dialog:not([open])') : null;

        if (hiddenHost) {
            focusTarget = hiddenHost.id ? document.querySelector(`[aria-controls="${hiddenHost.id}"]`) : null;
        }
        if (focusTarget) {
            focusTarget.focus({ preventScroll: true });
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
            submittedThisOpen = true;
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
    let visibleSeconds = 0;
    let timer = 0;

    const disarm = () => {
        window.removeEventListener('scroll', onScroll);
        document.removeEventListener('mouseout', onMouseOut);
        window.clearInterval(timer);
    };

    // trigger -- сработавшее правило ('scroll' | 'exit' | 'timer'), уходит параметром цели Метрики.
    const tryAutoShow = (trigger) => {
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
        open('auto', null, { trigger });
    };

    const onScroll = () => {
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;

        if (scrollable > 0 && window.scrollY / scrollable >= scrollShare) {
            tryAutoShow('scroll');
        }
    };

    // Уход курсора за верхний край окна браузера: только мышь.
    const onMouseOut = (event) => {
        if (event.relatedTarget === null && event.clientY <= 0) {
            tryAutoShow('exit');
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
            tryAutoShow('timer');
        }
    }, 1000);
})();

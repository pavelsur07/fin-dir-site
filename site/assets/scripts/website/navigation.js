(() => {
    const initializeMenu = () => {
        document.querySelectorAll('[data-vf-menu-open]').forEach((openButton) => {
            const dialog = document.getElementById(openButton.getAttribute('aria-controls'));

            if (!(dialog instanceof HTMLDialogElement)) {
                return;
            }

            const panel = dialog.querySelector('[data-vf-menu-panel]');
            const closeButton = dialog.querySelector('[data-vf-menu-close]');

            if (!(panel instanceof HTMLElement) || !(closeButton instanceof HTMLButtonElement)) {
                return;
            }

            let closeTimer;
            let restoreFocus = true;
            let lockedScrollX = 0;
            let lockedScrollY = 0;
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            const focusableElements = () => [...dialog.querySelectorAll('a[href], button:not(:disabled)')]
                .filter((element) => element.getClientRects().length > 0);

            const holdPagePosition = () => {
                window.scrollTo({ left: lockedScrollX, top: lockedScrollY, behavior: 'instant' });
            };

            const preventBackdropScroll = (event) => {
                if (!panel.contains(event.target)) {
                    event.preventDefault();
                }
            };

            const unlockPage = () => {
                window.removeEventListener('scroll', holdPagePosition);
                dialog.removeEventListener('wheel', preventBackdropScroll);
                dialog.removeEventListener('touchmove', preventBackdropScroll);
            };

            const finishClose = (event) => {
                if (event && (event.target !== panel || event.propertyName !== 'transform')) {
                    return;
                }

                window.clearTimeout(closeTimer);
                panel.removeEventListener('transitionend', finishClose);

                if (dialog.open) {
                    dialog.close();
                }

                dialog.dataset.state = 'closed';
                openButton.setAttribute('aria-expanded', 'false');
                unlockPage();

                if (restoreFocus && openButton.isConnected && getComputedStyle(openButton).display !== 'none') {
                    openButton.focus({ preventScroll: true });
                }
            };

            const requestClose = (shouldRestoreFocus = true) => {
                if (!dialog.open || dialog.dataset.state === 'closing') {
                    return;
                }

                restoreFocus = shouldRestoreFocus;
                dialog.dataset.state = 'closing';

                if (reducedMotion.matches) {
                    finishClose();

                    return;
                }

                panel.addEventListener('transitionend', finishClose, { once: true });
                closeTimer = window.setTimeout(finishClose, 250);
            };

            openButton.addEventListener('click', () => {
                if (dialog.open) {
                    return;
                }

                dialog.dataset.state = 'closed';
                lockedScrollX = window.scrollX;
                lockedScrollY = window.scrollY;
                dialog.showModal();
                lockPage();
                holdPagePosition();
                openButton.setAttribute('aria-expanded', 'true');

                panel.getBoundingClientRect();
                window.requestAnimationFrame(() => {
                    if (!dialog.open) {
                        return;
                    }

                    dialog.dataset.state = 'open';
                    closeButton.focus({ preventScroll: true });
                });
            });

            closeButton.addEventListener('click', () => requestClose());
            dialog.addEventListener('cancel', (event) => {
                event.preventDefault();
                requestClose();
            });
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    requestClose();
                }
            });
            dialog.addEventListener('keydown', (event) => {
                if (event.key !== 'Tab') {
                    const maxScroll = panel.scrollHeight - panel.clientHeight;
                    const upward = ['ArrowUp', 'PageUp', 'Home'].includes(event.key);
                    const downward = ['ArrowDown', 'PageDown', 'End', ' '].includes(event.key);

                    if (' ' === event.key && event.target instanceof HTMLButtonElement) {
                        return;
                    }

                    if ((upward && panel.scrollTop <= 0) || (downward && panel.scrollTop >= maxScroll)) {
                        event.preventDefault();
                    }

                    return;
                }

                const focusable = focusableElements();
                const first = focusable[0];
                const last = focusable.at(-1);

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last?.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first?.focus();
                }
            });
            window.addEventListener('resize', () => {
                if (dialog.open && getComputedStyle(openButton).display === 'none') {
                    restoreFocus = false;
                    finishClose();
                }
            });
        });
    };

    const initializeCookieNotice = () => {
        const cookieNotice = document.getElementById('cookieNotice');
        const cookieAccept = document.getElementById('cookieAccept');
        const cookieClose = document.getElementById('cookieClose');
        const cookieStorageKey = 'vf_cookie_notice_accepted';

        const hasAcceptedCookies = () => {
            try {
                return window.localStorage.getItem(cookieStorageKey) === '1';
            } catch (error) {
                return false;
            }
        };

        const saveCookieAcceptance = () => {
            try {
                window.localStorage.setItem(cookieStorageKey, '1');
            } catch (error) {
                console.warn('Не удалось сохранить согласие cookie в localStorage.', error);
            }
        };

        const showCookieNotice = () => {
            if (!cookieNotice || hasAcceptedCookies()) {
                return;
            }
            cookieNotice.hidden = false;
            window.requestAnimationFrame(() => {
                cookieNotice.className += ' is-visible';
            });
        };

        const hideCookieNotice = () => {
            if (!cookieNotice) {
                return;
            }
            cookieNotice.classList.remove('is-visible');
            window.setTimeout(() => {
                cookieNotice.hidden = true;
            }, 250);
        };

        const acceptCookies = () => {
            saveCookieAcceptance();
            hideCookieNotice();
        };

        if (cookieAccept) {
            cookieAccept.addEventListener('click', acceptCookies);
        }

        if (cookieClose) {
            cookieClose.addEventListener('click', hideCookieNotice);
        }

        showCookieNotice();
    };

    const initializeLeadForms = () => {
        const trackGoal = (goalName) => {
            const ymId = window.VF_ANALYTICS && window.VF_ANALYTICS.ymCounterId;

            if (typeof window.ym === 'function' && ymId && ymId !== 'YM_COUNTER_ID') {
                window.ym(ymId, 'reachGoal', goalName);
            }
        };

        document.querySelectorAll('.js-cta-click').forEach((element) => {
            element.addEventListener('click', () => {
                trackGoal('cta_click');
            });
        });

        document.querySelectorAll('.js-telegram-click').forEach((element) => {
            element.addEventListener('click', () => {
                trackGoal('telegram_click');
            });
        });

        const newSubmissionId = () => {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }

            // Старые браузеры: id пустой, сервер выдаст свой (защиты от повтора не будет).
            return '';
        };

        const setHidden = (form, name, value) => {
            let input = form.elements.namedItem(name);

            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                form.append(input);
            }

            input.value = value;
        };

        const clearErrors = (form) => {
            form.querySelectorAll('[data-vf-lead-field-error]').forEach((element) => element.remove());
            form.querySelectorAll('[aria-invalid="true"]').forEach((field) => {
                field.removeAttribute('aria-invalid');
                field.removeAttribute('aria-describedby');
            });
        };

        const showFieldError = (form, name, message) => {
            const field = form.elements.namedItem(name);

            if (!(field instanceof HTMLElement) || field.type === 'hidden') {
                return false;
            }

            const component = field.closest('[data-vf-component]');
            if (!component) {
                return false;
            }

            const feedback = document.createElement('div');
            feedback.className = 'text-small text-danger';
            feedback.id = `${field.id}-feedback`;
            feedback.dataset.vfLeadFieldError = '';
            feedback.textContent = message;

            // Подсветку даёт вариант aria-invalid:* в компоненте -- JS меняет только атрибуты.
            field.setAttribute('aria-invalid', 'true');
            field.setAttribute('aria-describedby', feedback.id);
            // У checkbox корень -- flex-строка: текст ошибки внутри сжал бы подпись.
            if (component.dataset.vfComponent === 'checkbox') {
                component.after(feedback);
            } else {
                component.append(feedback);
            }

            return true;
        };

        document.querySelectorAll('[data-vf-lead-form]').forEach((form) => {
            const success = form.querySelector('[data-vf-lead-success]');
            const failure = form.querySelector('[data-vf-lead-error]');
            const submit = form.querySelector('[type="submit"]');
            const submitLabel = submit ? submit.textContent : '';
            const fallback = 'Не удалось отправить заявку. Попробуйте ещё раз или напишите нам в Telegram: @vashfindir_ru.';

            let openedAt = performance.now();

            const referrerOrigin = () => {
                try {
                    return document.referrer ? new URL(document.referrer).origin : '';
                } catch (error) {
                    return '';
                }
            };

            const prepare = () => {
                openedAt = performance.now();
                setHidden(form, 'submission_id', newSubmissionId());
                setHidden(form, 'page_url', window.location.pathname);
                // Только origin: путь и query чужого сайта могут содержать персональные данные.
                setHidden(form, 'referrer', referrerOrigin());
                new URLSearchParams(window.location.search).forEach((value, key) => {
                    if (key.startsWith('utm_')) {
                        setHidden(form, key, value.slice(0, 200));
                    }
                });
            };

            prepare();

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearErrors(form);
                success.hidden = true;
                failure.hidden = true;
                submit.disabled = true;
                submit.textContent = 'Отправляем…';
                // Длительность по часам самого браузера: расхождение с сервером не важно.
                setHidden(form, 'fill_ms', String(Math.round(performance.now() - openedAt)));

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { Accept: 'application/json' },
                    });
                    const payload = await response.json().catch(() => ({}));

                    if (response.status === 201) {
                        // Цель -- только после ответа сервера: заявка действительно сохранена.
                        trackGoal('lead_form_submit');
                        form.reset();
                        prepare();
                        success.hidden = false;
                        return;
                    }

                    if (response.status === 422 && payload.errors) {
                        const unplaced = Object.entries(payload.errors)
                            .filter(([name, message]) => !showFieldError(form, name, message))
                            .map(([, message]) => message);
                        failure.textContent = unplaced.length > 0 ? unplaced.join(' ') : 'Проверьте поля формы.';
                        failure.hidden = false;
                        const firstInvalid = form.querySelector('[aria-invalid="true"]');
                        if (firstInvalid) {
                            firstInvalid.focus();
                        }
                        return;
                    }

                    failure.textContent = payload.error || fallback;
                    failure.hidden = false;
                } catch (error) {
                    failure.textContent = fallback;
                    failure.hidden = false;
                } finally {
                    submit.disabled = false;
                    submit.textContent = submitLabel;
                }
            });
        });
    };

    const initialize = () => {
        initializeMenu();
        initializeCookieNotice();
        initializeLeadForms();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();

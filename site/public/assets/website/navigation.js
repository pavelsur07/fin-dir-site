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
                cookieNotice.classList.add('is-visible');
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

        document.querySelectorAll('.js-lead-form').forEach((form) => {
            const successMessage = form.querySelector('.js-lead-success');

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const nameInput = form.elements.name;
                const contactInput = form.elements.contact;
                const name = nameInput ? nameInput.value.trim() : '';
                const contact = contactInput ? contactInput.value.trim() : '';

                if (!name || !contact) {
                    form.classList.add('was-validated');
                    return;
                }

                trackGoal('lead_form_submit');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (!response.ok) {
                        throw new Error('Form submit failed');
                    }
                } catch (error) {
                    console.warn('Форма не отправлена на сервер.', error);
                }

                if (successMessage) {
                    successMessage.classList.remove('hidden');
                }

                form.reset();
                form.classList.remove('was-validated');
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

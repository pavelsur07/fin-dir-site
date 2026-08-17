(() => {
    const initialize = () => {
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

            const lockPage = () => {
                window.addEventListener('scroll', holdPagePosition, { passive: true });
                dialog.addEventListener('wheel', preventBackdropScroll, { passive: false });
                dialog.addEventListener('touchmove', preventBackdropScroll, { passive: false });
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

                // Commit the off-canvas start state before transitioning it onscreen.
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();

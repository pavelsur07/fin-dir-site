(function () {
    function qs(sel, root) { return (root || document).querySelector(sel); }
    function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
    function on(el, ev, fn) { if (el) el.addEventListener(ev, fn); }

    document.addEventListener('DOMContentLoaded', function () {
        const openBtn = qs('[data-vf-mobile-open]');
        const mobile = qs('#vfMobileNav');
        const closeBtn = qs('[data-vf-mobile-close]');
        const backdrop = qs('.vf-mobile__backdrop');

        // safety: ensure hidden by default
        if (mobile && mobile.getAttribute('hidden') === null) {
            mobile.hidden = true;
        }

        // Active state like reference: aria-current="page"
        (function setActiveLinks() {
            const currentPath = (window.location.pathname || '/').replace(/\/+$/, '') || '/';
            const links = qsa('.vf-nav__link, .vf-mobile__link');

            links.forEach(function (a) {
                try {
                    const url = new URL(a.href, window.location.origin);
                    const linkPath = (url.pathname || '/').replace(/\/+$/, '') || '/';
                    if (linkPath === currentPath) {
                        a.setAttribute('aria-current', 'page');
                    } else {
                        a.removeAttribute('aria-current');
                    }
                } catch (e) {
                    // ignore invalid URLs
                }
            });
        })();

        let lastFocusedEl = null;

        function lockScroll() {
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        }

        function unlockScroll() {
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
        }

        function setExpanded(isOpen) {
            if (!openBtn) return;
            openBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        function focusFirstMenuLink() {
            if (!mobile) return;
            const firstLink = qs('.vf-mobile__link', mobile);
            if (firstLink) firstLink.focus();
        }

        function openMenu() {
            if (!mobile) return;
            lastFocusedEl = document.activeElement;
            mobile.hidden = false;
            lockScroll();
            setExpanded(true);
            window.setTimeout(focusFirstMenuLink, 0);
        }

        function closeMenu() {
            if (!mobile) return;
            mobile.hidden = true;
            unlockScroll();
            setExpanded(false);

            if (lastFocusedEl && typeof lastFocusedEl.focus === 'function') {
                lastFocusedEl.focus();
                lastFocusedEl = null;
            } else if (openBtn && typeof openBtn.focus === 'function') {
                openBtn.focus();
            }
        }

        on(openBtn, 'click', function (e) {
            e.preventDefault();
            openMenu();
        });

        on(closeBtn, 'click', function (e) {
            e.preventDefault();
            closeMenu();
        });

        on(backdrop, 'click', function () {
            closeMenu();
        });

        // Close on any click on a menu link (mobile)
        if (mobile) {
            on(mobile, 'click', function (e) {
                const a = e.target && e.target.closest ? e.target.closest('.vf-mobile__link') : null;
                if (a) closeMenu();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMenu();
        });
    });
})();

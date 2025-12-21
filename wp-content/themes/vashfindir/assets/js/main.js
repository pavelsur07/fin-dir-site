(function () {
    function qs(sel, root) { return (root || document).querySelector(sel); }
    function on(el, ev, fn) { if (el) el.addEventListener(ev, fn); }

    document.addEventListener('DOMContentLoaded', function () {
        const openBtn = qs('[data-vf-mobile-open]');
        const mobile = qs('.vf-mobile');
        const closeBtn = qs('[data-vf-mobile-close]');
        const backdrop = qs('.vf-mobile__backdrop');

        function openMenu() {
            if (!mobile) return;
            mobile.hidden = false;
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            if (!mobile) return;
            mobile.hidden = true;
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
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

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMenu();
        });

        // safety: if menu exists but no hidden attribute set in markup
        if (mobile && mobile.getAttribute('hidden') === null) {
            mobile.hidden = true;
        }
    });
})();

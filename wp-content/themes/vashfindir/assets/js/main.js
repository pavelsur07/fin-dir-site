// vashfindir theme js (placeholder)
(function () {
    const burger = document.querySelector('.vf-burger');
    const mobile = document.getElementById('vfMobileNav');
    if (!burger || !mobile) return;

    const closeBtn = mobile.querySelector('.vf-mobile__close');
    const backdrop = mobile.querySelector('[data-close="1"]');

    function openNav() {
        mobile.hidden = false;
        burger.setAttribute('aria-expanded', 'true');
        document.documentElement.style.overflow = 'hidden';
    }

    function closeNav() {
        mobile.hidden = true;
        burger.setAttribute('aria-expanded', 'false');
        document.documentElement.style.overflow = '';
    }

    burger.addEventListener('click', () => {
        const expanded = burger.getAttribute('aria-expanded') === 'true';
        expanded ? closeNav() : openNav();
    });

    closeBtn && closeBtn.addEventListener('click', closeNav);
    backdrop && backdrop.addEventListener('click', closeNav);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobile.hidden === false) closeNav();
    });
})();

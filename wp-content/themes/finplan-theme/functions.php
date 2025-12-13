<?php

// Подключаем стили и скрипты
function finplan_enqueue_assets() {
    // Bootstrap 5 CSS
    wp_enqueue_style(
        'bootstrap-5',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        [],
        '5.3.3'
    );

    // Стили темы (после Bootstrap, чтобы можно было переопределять)
    wp_enqueue_style(
        'finplan-style',
        get_stylesheet_uri(),
        ['bootstrap-5'],
        wp_get_theme()->get('Version')
    );

    // Bootstrap 5 JS (для адаптивного меню и пр.)
    wp_enqueue_script(
        'bootstrap-5',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        [],
        '5.3.3',
        true
    );

    $menu_js = <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
    const mobileMenu = document.getElementById('mobileMenu');
    const burger = document.querySelector('.nav-burger');
    const closeBtn = document.querySelector('.nav-close');
    const accordion = document.querySelector('.nav-accordion');
    const accordionPanel = document.querySelector('.nav-accordion-panel');
    const dropdown = document.querySelector('.nav-dropdown');
    const dropdownToggle = document.querySelector('.nav-dropdown-toggle');

    const openMobile = () => {
        if (!mobileMenu) return;
        mobileMenu.hidden = false;
        mobileMenu.classList.add('is-open');
        if (burger) burger.setAttribute('aria-expanded', 'true');
    };

    const closeMobile = () => {
        if (!mobileMenu) return;
        mobileMenu.classList.remove('is-open');
        mobileMenu.hidden = true;
        if (burger) burger.setAttribute('aria-expanded', 'false');
    };

    if (burger && mobileMenu) {
        burger.addEventListener('click', openMobile);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeMobile);
    }

    if (mobileMenu) {
        mobileMenu.addEventListener('click', function (event) {
            if (event.target === mobileMenu) {
                closeMobile();
            }
        });

        mobileMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeMobile);
        });
    }

    if (accordion && accordionPanel) {
        accordion.addEventListener('click', function () {
            const isHidden = accordionPanel.hasAttribute('hidden');
            accordionPanel.toggleAttribute('hidden');
            accordion.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });
    }

    if (dropdown && dropdownToggle) {
        const closeDropdown = () => {
            dropdown.classList.remove('is-open');
            dropdownToggle.setAttribute('aria-expanded', 'false');
        };

        dropdownToggle.addEventListener('click', function (event) {
            event.preventDefault();
            const willOpen = !dropdown.classList.contains('is-open');
            dropdown.classList.toggle('is-open', willOpen);
            dropdownToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!dropdown.contains(event.target)) {
                closeDropdown();
            }
        });
    }
});
JS;

    wp_add_inline_script('bootstrap-5', $menu_js);
}
add_action('wp_enqueue_scripts', 'finplan_enqueue_assets');

// Базовая поддержка фич
function finplan_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption']);

    register_nav_menus([
        'primary'         => __('Header: основное меню', 'finplan'),
        'mega_analytics'  => __('Header: мегаменю — Аналитика', 'finplan'),
        'mega_planning'   => __('Header: мегаменю — Планирование', 'finplan'),
    ]);
}
add_action('after_setup_theme', 'finplan_theme_setup');

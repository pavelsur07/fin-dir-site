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
}
add_action('wp_enqueue_scripts', 'finplan_enqueue_assets');

// Базовая поддержка фич
function finplan_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption']);
    register_nav_menus([
        'primary' => __('Primary Menu', 'finplan'),
    ]);
}
add_action('after_setup_theme', 'finplan_theme_setup');

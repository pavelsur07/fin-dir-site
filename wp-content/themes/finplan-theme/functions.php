<?php

// Подключаем стили
function finplan_enqueue_assets() {
    wp_enqueue_style(
        'finplan-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
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

<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function () {
    $ver = wp_get_theme()->get('Version');

    // базовые стили
    wp_enqueue_style('vashfindir-main', get_template_directory_uri() . '/assets/css/main.css', [], $ver);

    // условные стили по типам страниц
    if (is_front_page() || is_page(['service', 'service-findir', 'academy-finance'])) {
        wp_enqueue_style('vashfindir-landing', get_template_directory_uri() . '/assets/css/landing.css', ['vashfindir-main'], $ver);
    }

    if (is_page('about')) {
        wp_enqueue_style('vashfindir-info', get_template_directory_uri() . '/assets/css/info.css', ['vashfindir-main'], $ver);
    }

    // blog.css можно подключить позже, когда заведёте archive/single
    // if (is_home() || is_archive() || is_single()) { ... }

    wp_enqueue_script('vashfindir-main', get_template_directory_uri() . '/assets/js/main.js', [], $ver, true);
});

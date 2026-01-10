<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function () {
    $ver = wp_get_theme()->get('Version');

    // базовые стили
    wp_enqueue_style('vashfindir-main', get_template_directory_uri() . '/assets/css/main.css', [], $ver);

    $design_system_template = '';
    $qo_id = get_queried_object_id();
    if ($qo_id) {
        $design_system_template = get_page_template_slug($qo_id);
    }
    $is_design_system_sandbox = ($design_system_template === 'page-design-system.php');

    // условные стили по типам страниц
    if (is_front_page() || is_page(['service', 'service-findir', 'academy-finance', 'design-system']) || $is_design_system_sandbox) {

        // Bootstrap Icons (нужно для bi bi-check-circle-fill и других bi-иконок)
        // Подключаем только на "landing"-страницах, чтобы не влиять на остальной сайт.
        wp_enqueue_style(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
            [],
            '1.11.3'
        );

        wp_enqueue_style(
            'vashfindir-landing',
            get_template_directory_uri() . '/assets/css/landing.css',
            ['vashfindir-main', 'bootstrap-icons'],
            $ver
        );
    }

    if (is_page('about')) {
        wp_enqueue_style('vashfindir-info', get_template_directory_uri() . '/assets/css/info.css', ['vashfindir-main'], $ver);
    }

    // blog.css можно подключить позже, когда заведёте archive/single
    // if (is_home() || is_archive() || is_single()) { ... }

    wp_enqueue_script('vashfindir-main', get_template_directory_uri() . '/assets/js/main.js', [], $ver, true);
});

<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function () {
    $ver = wp_get_theme()->get('Version');
    $bootstrap_only = defined('VF_BOOTSTRAP_ONLY') && VF_BOOTSTRAP_ONLY;

    // Bootstrap 5
    wp_enqueue_style(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        [],
        '5.3.3'
    );

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

        if (!$bootstrap_only) {
            wp_enqueue_style(
                'vashfindir-landing',
                get_template_directory_uri() . '/assets/css/landing.css',
                ['vashfindir-main', 'bootstrap-icons'],
                $ver
            );
        }
    }

    if (!$bootstrap_only) {
        // базовые стили
        wp_enqueue_style('vashfindir-main', get_template_directory_uri() . '/assets/css/main.css', ['bootstrap'], $ver);
    }

    if (is_page('about') && !$bootstrap_only) {
        wp_enqueue_style('vashfindir-info', get_template_directory_uri() . '/assets/css/info.css', ['vashfindir-main'], $ver);
    }

    // blog.css можно подключить позже, когда заведёте archive/single
    // if (is_home() || is_archive() || is_single()) { ... }

    if (!$bootstrap_only) {
        wp_enqueue_script('vashfindir-main', get_template_directory_uri() . '/assets/js/main.js', [], $ver, true);
    }

    wp_enqueue_script(
        'bootstrap-bundle',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        [],
        '5.3.3',
        true
    );
});

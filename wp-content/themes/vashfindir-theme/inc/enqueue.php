<?php

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'vfd-bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        [],
        THEME_VERSION
    );

    wp_enqueue_style(
        'vfd-bootstrap-icons',
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
        [],
        THEME_VERSION
    );

    wp_enqueue_style(
        'vfd-theme',
        get_theme_file_uri('/assets/css/theme.css'),
        ['vfd-bootstrap'],
        THEME_VERSION
    );

    wp_enqueue_script(
        'vfd-bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        [],
        THEME_VERSION,
        true
    );

    wp_enqueue_script(
        'vfd-theme',
        get_theme_file_uri('/assets/js/theme.js'),
        [],
        THEME_VERSION,
        true
    );
});

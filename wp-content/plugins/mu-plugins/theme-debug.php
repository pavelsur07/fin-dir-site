<?php
/**
 * MU Plugin: Theme Debug
 */

add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="notice notice-error"><pre>';

    echo "WP_CONTENT_DIR:\n";
    echo WP_CONTENT_DIR . "\n\n";

    echo "get_theme_root():\n";
    echo get_theme_root() . "\n\n";

    echo "Themes found by WP:\n";
    $themes = wp_get_themes();
    foreach ($themes as $slug => $theme) {
        echo "- {$slug} | Name: " . $theme->get('Name') . "\n";
    }

    echo "\nFiles in theme root:\n";
    $dirs = scandir(get_theme_root());
    foreach ($dirs as $d) {
        if ($d !== '.' && $d !== '..') {
            echo "- {$d}\n";
        }
    }

    echo '</pre></div>';
});

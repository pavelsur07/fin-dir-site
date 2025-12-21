<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Minimal SEO meta.
 * Важно: сделано аккуратно, без конфликта с SEO-плагинами.
 */
add_action('wp_head', function () {

    // Если установлен популярный SEO-плагин, часто он сам выводит мета.
    // Мы оставляем минимальную базу, но без жёстких конфликтов.
    // При необходимости позже добавим опции/фильтры и детект конкретных плагинов.

    $description = '';

    if (is_singular()) {
        global $post;
        if ($post instanceof WP_Post) {
            $excerpt = get_the_excerpt($post);
            if (is_string($excerpt) && $excerpt !== '') {
                $description = wp_strip_all_tags($excerpt);
            }
        }
    }

    if ($description === '') {
        $tagline = get_bloginfo('description');
        if (is_string($tagline)) {
            $description = $tagline;
        }
    }

    if ($description !== '') {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    echo "<meta name=\"robots\" content=\"index,follow\">\n";
}, 1);

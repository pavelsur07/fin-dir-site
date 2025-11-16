<?php
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="site-header">
    <div class="site-header-inner">
        <div class="site-logo">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo-link">
                <img
                        src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-vashfindir.svg' ); ?>"
                        alt="Ваш ФинДиректор"
                >
            </a>
        </div>
        <nav class="main-nav">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'fallback_cb'    => false,
                'items_wrap'     => '%3$s', // печатаем только <li>...</li>
            ]);
            ?>
            <a href="#demo" class="btn-primary">Запросить разбор</a>
        </nav>
    </div>
</header>

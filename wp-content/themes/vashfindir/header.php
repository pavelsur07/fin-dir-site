<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site-container">
    <header class="site-header">
        <h1 class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></h1>
        <?php if (get_bloginfo('description')) : ?>
            <p class="site-description"><?php bloginfo('description'); ?></p>
        <?php endif; ?>

        <?php if (has_nav_menu('primary')) : ?>
            <nav class="nav-primary" aria-label="<?php esc_attr_e('Primary Menu', 'vashfindir'); ?>">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'menu',
                ]);
                ?>
            </nav>
        <?php endif; ?>
    </header>

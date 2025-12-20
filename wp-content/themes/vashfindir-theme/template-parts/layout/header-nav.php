<?php
$has_menu = has_nav_menu('primary');
?>
<header class="border-bottom mb-4">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primaryNavbar" aria-controls="primaryNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="primaryNavbar">
                <?php if ($has_menu) : ?>
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => 'navbar-nav me-auto mb-2 mb-lg-0',
                        'fallback_cb'    => false,
                    ]);
                    ?>
                <?php else : ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', THEME_TEXTDOMAIN); ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo esc_url(home_url('/service/')); ?>"><?php esc_html_e('Service', THEME_TEXTDOMAIN); ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo esc_url(home_url('/cfo/')); ?>"><?php esc_html_e('CFO', THEME_TEXTDOMAIN); ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo esc_url(home_url('/about/')); ?>"><?php esc_html_e('About', THEME_TEXTDOMAIN); ?></a></li>
                    </ul>
                <?php endif; ?>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary" href="#login"><?php esc_html_e('Войти', THEME_TEXTDOMAIN); ?></a>
                    <a class="btn btn-primary" href="#register"><?php esc_html_e('Регистрация', THEME_TEXTDOMAIN); ?></a>
                </div>
            </div>
        </div>
    </nav>
</header>

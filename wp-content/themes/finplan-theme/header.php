<?php
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php wp_head(); ?>

    <meta name="yandex-verification" content="dcb00aeaeb0154b8" />
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=105455340', 'ym');

        ym(105455340, 'init', {ssr:true, webvisor:true, clickmap:true, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/105455340" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->

</head>
<body <?php body_class(); ?>>

<header class="site-header">
    <div class="site-header-inner container">
        <div class="site-logo">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo-link">
                <img
                        src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-vashfindir.svg' ); ?>"
                        alt="Ваш ФинДиректор"
                >
            </a>
        </div>
       <!-- <nav class="main-nav">
            <?php
/*            wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'fallback_cb'    => false,
                'items_wrap'     => '%3$s', // печатаем только <li>...</li>
            ]);
            */?>
            <a href="#cta-consult" class="btn-primary">Бесплатная диагностика</a>
        </nav>-->
        <nav class="main-nav" aria-label="Главное меню">

            <!-- Mobile burger -->
            <button class="nav-burger" type="button" aria-label="Открыть меню" aria-expanded="false" aria-controls="mobileMenu">
                <span></span><span></span><span></span>
            </button>

            <!-- Desktop -->
            <div class="nav-desktop">
                <ul class="nav-list">

                    <!-- Мегаменю: "Возможности" -->
                    <li class="nav-item nav-dropdown">
                        <a class="nav-link nav-dropdown-toggle" href="#" aria-haspopup="true" aria-expanded="false">
                            Возможности <span class="nav-caret">▾</span>
                        </a>

                        <div class="nav-dropdown-panel" role="menu">
                            <div class="nav-dropdown-grid">
                                <div class="nav-dropdown-col">
                                    <div class="nav-dropdown-title">Аналитика и отчёты</div>
                                    <ul class="nav-dropdown-ul">
                                        <?php
                                        wp_nav_menu([
                                                'theme_location' => 'mega_analytics',
                                                'container'      => false,
                                                'fallback_cb'    => false,
                                                'items_wrap'     => '%3$s',
                                        ]);
                                        ?>
                                    </ul>
                                </div>

                                <div class="nav-dropdown-col">
                                    <div class="nav-dropdown-title">Планирование и контроль</div>
                                    <ul class="nav-dropdown-ul">
                                        <?php
                                        wp_nav_menu([
                                                'theme_location' => 'mega_planning',
                                                'container'      => false,
                                                'fallback_cb'    => false,
                                                'items_wrap'     => '%3$s',
                                        ]);
                                        ?>
                                    </ul>
                                </div>

                                <div class="nav-dropdown-cta">
                                    <a href="#cta-consult" class="btn-primary btn-block">Бесплатная диагностика</a>
                                    <a href="#template" class="btn-outline-primary btn-block">Скачать шаблон ДДС</a>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- Основное меню из админки -->
                    <?php
                    wp_nav_menu([
                            'theme_location' => 'primary',
                            'container'      => false,
                            'fallback_cb'    => false,
                            'items_wrap'     => '%3$s',
                    ]);
                    ?>
                </ul>

                <div class="nav-actions">
                    <a href="#cta-consult" class="btn-primary">Бесплатная диагностика</a>
                </div>
            </div>

            <!-- Mobile -->
            <div id="mobileMenu" class="nav-mobile" hidden>
                <div class="nav-mobile-inner">
                    <div class="nav-mobile-header">
                        <span class="nav-mobile-title">Меню</span>
                        <button class="nav-close" type="button" aria-label="Закрыть меню">✕</button>
                    </div>

                    <div class="nav-mobile-section">
                        <button class="nav-accordion" type="button" aria-expanded="false">
                            Возможности <span class="nav-caret">▾</span>
                        </button>

                        <div class="nav-accordion-panel" hidden>
                            <div class="nav-acc-title">Аналитика и отчёты</div>
                            <div class="nav-mobile-links">
                                <?php
                                wp_nav_menu([
                                        'theme_location' => 'mega_analytics',
                                        'container'      => false,
                                        'fallback_cb'    => false,
                                        'menu_class'     => 'nav-mobile-wp',
                                ]);
                                ?>
                            </div>

                            <div class="nav-acc-title mt-12">Планирование и контроль</div>
                            <div class="nav-mobile-links">
                                <?php
                                wp_nav_menu([
                                        'theme_location' => 'mega_planning',
                                        'container'      => false,
                                        'fallback_cb'    => false,
                                        'menu_class'     => 'nav-mobile-wp',
                                ]);
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="nav-mobile-links">
                        <?php
                        wp_nav_menu([
                                'theme_location' => 'primary',
                                'container'      => false,
                                'fallback_cb'    => false,
                                'menu_class'     => 'nav-mobile-wp',
                        ]);
                        ?>
                    </div>

                    <div class="nav-mobile-actions">
                        <a href="#cta-consult" class="btn-primary btn-block">Бесплатная диагностика</a>
                        <a href="#template" class="btn-outline-primary btn-block">Скачать шаблон ДДС</a>
                    </div>
                </div>
            </div>
        </nav>


    </div>
</header>

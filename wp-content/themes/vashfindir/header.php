<?php
/**
 * Header
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="site-container">
    <header class="vf-header" role="banner">
        <div class="vf-header__inner">
            <a class="vf-logo-link" href="<?= esc_url(home_url('/')) ?>" aria-label="Ваш финдир — на главную">
                <img
                        class="vf-logo"
                        src="<?= esc_url(get_theme_file_uri('assets/img/logo-vf-on-light.png')) ?>"
                        width="360"
                        height="94"
                        alt="Ваш финдир"
                        decoding="async"
                        fetchpriority="high"
                >
            </a>

            <button class="vf-burger"
                    type="button"
                    aria-label="Открыть меню"
                    aria-controls="vfMobileNav"
                    aria-expanded="false"
                    data-vf-mobile-open>
                <span class="vf-burger__lines" aria-hidden="true"></span>
            </button>

            <nav class="vf-nav" aria-label="Основная навигация">
                <a class="vf-nav__link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a>
                <a class="vf-nav__link" href="<?php echo esc_url(home_url('/service/')); ?>">Сервис</a>
                <a class="vf-nav__link" href="<?php echo esc_url(home_url('/service-findir/')); ?>">Сервис + Финдир</a>
                <a class="vf-nav__link" href="<?php echo esc_url(home_url('/academy-finance/')); ?>">Академия</a>
                <a class="vf-nav__link" href="<?php echo esc_url(home_url('/about/')); ?>">О нас</a>
            </nav>

            <div class="vf-header__actions">
                <a class="vf-btn vf-btn--secondary"
                   href="https://app.vashfindir.ru/login"
                   target="_blank" rel="noopener">Войти</a>

                <a class="vf-btn vf-btn--primary"
                   href="https://app.vashfindir.ru/register"
                   target="_blank" rel="noopener">Регистрация</a>
            </div>
        </div>

        <!-- Mobile drawer -->
        <div class="vf-mobile" id="vfMobileNav" hidden>
            <div class="vf-mobile__panel" role="dialog" aria-modal="true" aria-label="Меню">
                <div class="vf-mobile__head">
                    <div class="vf-mobile__title">Меню</div>
                    <button class="vf-mobile__close"
                            type="button"
                            aria-label="Закрыть меню"
                            data-vf-mobile-close>×</button>
                </div>

                <div class="vf-mobile__links">
                    <a class="vf-mobile__link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a>
                    <a class="vf-mobile__link" href="<?php echo esc_url(home_url('/service/')); ?>">Сервис</a>
                    <a class="vf-mobile__link" href="<?php echo esc_url(home_url('/service-findir/')); ?>">Сервис + Финдир</a>
                    <a class="vf-mobile__link" href="<?php echo esc_url(home_url('/academy-finance/')); ?>">Академия</a>
                    <a class="vf-mobile__link" href="<?php echo esc_url(home_url('/about/')); ?>">О нас</a>
                </div>

                <div class="vf-mobile__actions">
                    <a class="vf-btn vf-btn--secondary vf-btn--block"
                       href="https://app.vashfindir.ru/login"
                       target="_blank" rel="noopener">Войти</a>

                    <a class="vf-btn vf-btn--primary vf-btn--block"
                       href="https://app.vashfindir.ru/register"
                       target="_blank" rel="noopener">Регистрация</a>
                </div>
            </div>

            <div class="vf-mobile__backdrop" aria-hidden="true"></div>
        </div>
    </header>

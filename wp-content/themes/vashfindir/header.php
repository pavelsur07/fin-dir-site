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
            <a class="vf-brand" href="<?php echo esc_url(home_url('/')); ?>">
                <span class="vf-brand__badge">ВФ</span>
                <span class="vf-brand__name">Ваш финдир</span>
            </a>

            <button class="vf-burger"
                    type="button"
                    aria-label="Открыть меню"
                    aria-controls="vfMobileNav"
                    aria-expanded="false">
                <span class="vf-burger__lines" aria-hidden="true"></span>
            </button>

            <nav class="vf-nav" aria-label="Основная навигация">
                <a class="vf-nav__link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a>
                <a class="vf-nav__link" href="<?php echo esc_url(home_url('/service/')); ?>">Сервис</a>
                <a class="vf-nav__link" href="<?php echo esc_url(home_url('/service-findir/')); ?>">Сервис + Финдир</a>
                <a class="vf-nav__link" href="<?php echo esc_url(home_url('/about/')); ?>">О нас</a>
            </nav>

            <div class="vf-header__actions">
                <a class="vf-btn vf-btn--secondary" href="<?php echo esc_url(home_url('/login/')); ?>">Войти</a>
                <a class="vf-btn vf-btn--primary" href="<?php echo esc_url(home_url('/register/')); ?>">Регистрация</a>
            </div>
        </div>

        <!-- Mobile drawer -->
        <div class="vf-mobile" id="vfMobileNav" hidden>
            <div class="vf-mobile__panel" role="dialog" aria-label="Меню">
                <div class="vf-mobile__head">
                    <div class="vf-mobile__title">Меню</div>
                    <button class="vf-mobile__close" type="button" aria-label="Закрыть меню">×</button>
                </div>

                <div class="vf-mobile__links">
                    <a class="vf-mobile__link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a>
                    <a class="vf-mobile__link" href="<?php echo esc_url(home_url('/service/')); ?>">Сервис</a>
                    <a class="vf-mobile__link" href="<?php echo esc_url(home_url('/service-findir/')); ?>">Сервис + Финдир</a>
                    <a class="vf-mobile__link" href="<?php echo esc_url(home_url('/about/')); ?>">О нас</a>
                </div>

                <div class="vf-mobile__actions">
                    <a class="vf-btn vf-btn--secondary vf-btn--block" href="<?php echo esc_url(home_url('/login/')); ?>">Войти</a>
                    <a class="vf-btn vf-btn--primary vf-btn--block" href="<?php echo esc_url(home_url('/register/')); ?>">Регистрация</a>
                </div>
            </div>
            <div class="vf-mobile__backdrop" data-close="1" aria-hidden="true"></div>
        </div>
    </header>

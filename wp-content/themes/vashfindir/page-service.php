<?php
if (!defined('ABSPATH')) exit;
get_header();

$hero_args = [
        'kicker' => 'Сервис + консультации',
        'title'  => 'Финансовая система, которую вы ведёте сами',
        'lead'   => get_the_excerpt() ?: 'Подключите учёт денег и P&L, настройте структуру и получайте консультации по мере необходимости.',
        'primary' => [
                'label' => 'Начать сервис',
                'url'   => home_url('/register'),
        ],
        'secondary' => [
                'label' => 'Войти',
                'url'   => home_url('/login'),
        ],
        'media' => [
                'type'   => 'image',
                'ratio'  => 'wide',
                'alt'    => 'Сервис «Ваш ФинДиректор» — интерфейс и процесс',
                'desktop'=> get_template_directory_uri() . '/assets/img/hero/desktop-hero-service.jpg',
                'tablet' => get_template_directory_uri() . '/assets/img/hero/tablet-hero-service.jpg',
                'mobile' => get_template_directory_uri() . '/assets/img/hero/mobile-hero-service.jpg',
        ],
];
?>

<main class="main-content" role="main">
    <?php get_template_part('template-parts/landing/hero-wide', null, $hero_args); ?>
    <?php get_template_part('template-parts/landing/sections'); ?>
</main>

<?php get_footer(); ?>

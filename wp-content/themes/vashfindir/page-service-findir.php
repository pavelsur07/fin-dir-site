<?php
if (!defined('ABSPATH')) exit;
get_header();

$hero_args = [
        'kicker' => 'Сервис + финдиректор',
        'title'  => 'Финансовая система с сопровождением Финдира',
        'lead'   => get_the_excerpt() ?: 'Настроим структуру, правила учёта и контроль. Вы получаете систему и финансовое управление, а не “отчёты ради отчётов”.',
        'primary' => [
                'label' => 'Работать с финдиром',
                'url'   => home_url('/register'),
        ],
        'secondary' => [
                'label' => 'Войти',
                'url'   => home_url('/login'),
        ],
        'media' => [
                'type'   => 'image',
                'ratio'  => 'wide',
                'alt'    => 'Сервис + финансовый директор — сопровождение и система',
                'desktop'=> get_template_directory_uri() . '/assets/img/hero/desktop-hero-service-findir.jpg',
                'tablet' => get_template_directory_uri() . '/assets/img/hero/tablet-hero-service-findir.jpg',
                'mobile' => get_template_directory_uri() . '/assets/img/hero/mobile-hero-service-findir.jpg',
        ],
];
?>

<main class="main-content" role="main">
    <?php get_template_part('template-parts/landing/hero-wide', null, $hero_args); ?>
    <?php get_template_part('template-parts/landing/sections'); ?>
</main>

<?php get_footer(); ?>

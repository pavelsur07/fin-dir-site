<?php
if (!defined('ABSPATH')) exit;
get_header();
?>

<main class="main-content" role="main">
    <?php get_template_part('template-parts/landing/hero-front-page'); ?>

    <?php
    // Home-only section: Control Level (reference block id=vf-control-level)
    get_template_part('template-parts/landing/control-level');
    ?>

    <?php get_template_part('template-parts/landing/sections'); ?>
</main>

<?php get_footer(); ?>



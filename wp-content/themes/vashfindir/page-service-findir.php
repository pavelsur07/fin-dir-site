<?php
if (!defined('ABSPATH')) exit;
get_header();
?>

<main class="main-content" role="main">
    <?php get_template_part('template-parts/landing/hero'); ?>
    <?php get_template_part('template-parts/landing/sections'); ?>
</main>

<?php get_footer(); ?>

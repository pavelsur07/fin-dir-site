<?php
if (!defined('ABSPATH')) exit;
get_header();
?>

<main class="main-content" role="main">
    <?php get_template_part('template-parts/landing/hero-front-page'); ?>
    <?php get_template_part('template-parts/landing/who'); ?>
    <?php get_template_part('template-parts/landing/why'); ?>
    <?php get_template_part('template-parts/landing/integrations'); ?>
    <?php get_template_part('template-parts/landing/lead-capture'); ?>
    <?php get_template_part('template-parts/landing/outcome'); ?>
    <?php get_template_part('template-parts/landing/control-level'); ?>
    <?php get_template_part('template-parts/blocks/tariffs-2'); ?>
    <?php get_template_part('template-parts/landing/trust'); ?>
    <?php get_template_part('template-parts/landing/faq'); ?>
</main>

<?php get_footer(); ?>

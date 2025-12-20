<?php
/*
 * Template Name: Landing
 */
?>
<?php get_header(); ?>
<main class="container py-5">
    <?php while (have_posts()) : the_post(); ?>
        <h1 class="mb-3"><?php the_title(); ?></h1>
        <div class="entry-content"><?php the_content(); ?></div>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>

<?php
/**
 * Main template file for vashfindir theme.
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main class="main-content" role="main">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <div class="entry-excerpt">
                    <?php the_excerpt(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <p class="no-content">Тема vashfindir активна. Контент появится здесь.</p>
    <?php endif; ?>
</main>

<?php
get_footer();

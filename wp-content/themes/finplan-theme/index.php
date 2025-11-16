<?php
get_header();
?>

    <main class="content-wrapper">
        <?php if (have_posts()) : ?>
            <h1 class="section-title">Блог о деньгах бизнеса</h1>
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class(); ?>>
                    <h2 class="post-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    <div class="post-meta">
                        <?php echo get_the_date(); ?> · <?php the_category(', '); ?>
                    </div>
                    <div class="post-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                </article>
                <hr>
            <?php endwhile; ?>

            <div class="pagination">
                <?php the_posts_pagination(); ?>
            </div>
        <?php else : ?>
            <p>Пока нет материалов.</p>
        <?php endif; ?>
    </main>

<?php
get_footer();

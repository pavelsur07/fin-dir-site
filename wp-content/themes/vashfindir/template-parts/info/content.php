<?php if (!defined('ABSPATH')) exit; ?>

<article class="vf-info container">
    <header class="vf-info__header">
        <h1 class="vf-info__title"><?php the_title(); ?></h1>
    </header>

    <div class="vf-info__content">
        <?php
        while (have_posts()) {
            the_post();
            the_content();
        }
        ?>
    </div>
</article>

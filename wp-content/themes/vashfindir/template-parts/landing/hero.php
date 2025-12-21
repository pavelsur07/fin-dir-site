<?php if (!defined('ABSPATH')) exit; ?>

<section class="vf-hero">
    <div class="vf-hero__inner container">
        <h1 class="vf-hero__title"><?php echo esc_html(get_the_title()); ?></h1>

        <p class="vf-hero__lead ">
            <?php
            $lead = get_the_excerpt();
            echo $lead ? esc_html($lead) : esc_html('Короткий лид будет добавлен по референсу (landing).');
            ?>
        </p>

        <div class="vf-hero__actions">
            <a class="btn btn-primary" href="<?php echo esc_url(home_url('/register')); ?>">Регистрация</a>
            <a class="btn btn-outline" href="<?php echo esc_url(home_url('/login')); ?>">Войти</a>
        </div>
    </div>
</section>

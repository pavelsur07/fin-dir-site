<?php if (!defined('ABSPATH')) exit; ?>

<section class="vf-hero4" aria-labelledby="vf-hero4-title">
    <div class="container">
        <div class="vf-hero4__card">
            <div class="vf-hero4__grid">
                <!-- Left: content -->
                <div class="vf-hero4__content">
                    <span class="vf-hero4__kicker">Сервис + финдиректор</span>

                    <h1 class="vf-hero4__title" id="vf-hero4-title">Финансовая система для роста, а не отчётов</h1>

                    <p class="vf-hero4__lead">
                        Устойчивый рост и порядок в финансах бизнеса —<br>
                        за счёт системы, а не ручного контроля.
                    </p>

                    <div class="vf-hero4__actions">
                        <a class="vf-btn vf-btn--primary" href="<?php echo esc_url(home_url('/service')); ?>">Веду сам</a>
                        <a class="vf-btn vf-btn--secondary" href="<?php echo esc_url(home_url('/service-findir')); ?>">Доверить финансы профи</a>
                    </div>
                </div>

                <!-- Right: real image -->
                <div class="vf-hero4__media">
                    <div class="vf-hero4__media-wrap">
                        <picture class="vf-hero4__picture">
                            <source media="(max-width: 575.98px)" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/img/hero/mobile-hero-home.jpg'); ?>">
                            <source media="(max-width: 991.98px)" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/img/hero/tablet-hero-home.jpg'); ?>">
                            <img
                                    class="vf-hero4__image"
                                    src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/hero/desktop-hero-home.jpg'); ?>"
                                    alt="Ваш ФинДиректор — сервис и финдиректор для устойчивого роста"
                                    loading="eager"
                                    decoding="async"
                                    fetchpriority="high"
                            >
                        </picture>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

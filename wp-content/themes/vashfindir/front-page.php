<?php
if (!defined('ABSPATH')) exit;
get_header();
?>

<main class="main-content" role="main">
    <?php get_template_part('template-parts/landing/hero-front-page'); ?>

    <!-- WHO IT'S FOR -->
    <section class="vf-who vf-section-pad-lg vf-anchor" id="who">
        <div class="container">
            <div class="vf-who__head">
                <h2 class="section-title">Кому это обычно откликается</h2>
                <p class="section-subtitle">
                    Мы работаем с владельцами бизнеса, которые уже зарабатывают, но не чувствуют финансовой опоры
                    и принимают решения «на ощущениях», потому что цифры не дают ясности.
                </p>
            </div>

            <div class="vf-who__grid" role="list">
                <div class="vf-who__item" role="listitem">
                    <div class="vf-who__card vf-surface">
                        <div class="vf-who__icon" aria-hidden="true"><i class="bi bi-graph-up"></i></div>
                        <div>
                            <h3 class="vf-who__item-title">Есть рост</h3>
                            <p class="vf-who__item-text">Выручка растёт, но уверенности меньше</p>
                        </div>
                    </div>
                </div>

                <div class="vf-who__item" role="listitem">
                    <div class="vf-who__card vf-surface">
                        <div class="vf-who__icon" aria-hidden="true"><i class="bi bi-lightning-charge"></i></div>
                        <div>
                            <h3 class="vf-who__item-title">Решения на нервах</h3>
                            <p class="vf-who__item-text">Каждый шаг ощущается как риск</p>
                        </div>
                    </div>
                </div>

                <div class="vf-who__item" role="listitem">
                    <div class="vf-who__card vf-surface">
                        <div class="vf-who__icon" aria-hidden="true"><i class="bi bi-clipboard2-check"></i></div>
                        <div>
                            <h3 class="vf-who__item-title">Не нужны «советы»</h3>
                            <p class="vf-who__item-text">Нужна ясность по вашей ситуации</p>
                        </div>
                    </div>
                </div>

                <div class="vf-who__item" role="listitem">
                    <div class="vf-who__card vf-surface">
                        <div class="vf-who__icon" aria-hidden="true"><i class="bi bi-shop-window"></i></div>
                        <div>
                            <h3 class="vf-who__item-title">E-commerce / маркетплейсы</h3>
                            <p class="vf-who__item-text">Обороты есть — деньги «гуляют»</p>
                        </div>
                    </div>
                </div>

                <div class="vf-who__item" role="listitem">
                    <div class="vf-who__card vf-surface">
                        <div class="vf-who__icon" aria-hidden="true"><i class="bi bi-briefcase"></i></div>
                        <div>
                            <h3 class="vf-who__item-title">Сервисный бизнес</h3>
                            <p class="vf-who__item-text">Сложно держать маржу и кэш</p>
                        </div>
                    </div>
                </div>

                <div class="vf-who__item" role="listitem">
                    <div class="vf-who__card vf-surface">
                        <div class="vf-who__icon" aria-hidden="true"><i class="bi bi-people"></i></div>
                        <div>
                            <h3 class="vf-who__item-title">SMB без учёта</h3>
                            <p class="vf-who__item-text">Цифры есть, системы — нет</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    // Home-only section: Control Level (reference block id=vf-control-level)
    get_template_part('template-parts/landing/control-level');
    ?>

    <?php get_template_part('template-parts/landing/sections'); ?>

    <?php
    // Home CTA: reusable lead capture form with modal (ref section id="lead-form-2")
    get_template_part('template-parts/landing/lead-capture');
    ?>
</main>

<?php get_footer(); ?>

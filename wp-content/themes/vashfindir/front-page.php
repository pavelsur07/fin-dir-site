<?php
if (!defined('ABSPATH')) exit;
get_header();
?>

<main class="main-content" role="main">
    <?php get_template_part('template-parts/landing/hero-front-page'); ?>

    <!-- WHO IT'S FOR -->
    <section class="vf-control vf-section-pad-lg">
        <div class="container-xxl vf-anchor" id="who">
            <div class="vf-control__head">
                <h2 class="section-title vf-control__title">Кому это обычно откликается</h2>
                <p class="section-subtitle vf-faq__subtitle">
                    Мы работаем с владельцами бизнеса, которые уже зарабатывают, но не чувствуют финансовой опоры
                    и принимают решения «на ощущениях», потому что цифры не дают ясности.
                </p>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                                <div class="vf-control__icon"><i class="bi bi-graph-up"></i></div>
                                <div>
                                    <div class="vf-control__h3">Есть рост</div>
                                    <div class="vf-control__lead">Выручка растёт, но уверенности меньше</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                                <div class="vf-control__icon"><i class="bi bi-lightning-charge"></i></div>
                                <div>
                                    <div class="vf-control__h3">Решения на нервах</div>
                                    <div class="vf-control__lead">Каждый шаг ощущается как риск</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                                <div class="vf-control__icon"><i class="bi bi-clipboard2-check"></i></div>
                                <div>
                                    <div class="vf-control__h3">Не нужны «советы»</div>
                                    <div class="vf-control__lead">Нужна ясность по вашей ситуации</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                                <div class="vf-control__icon"><i class="bi bi-shop-window"></i></div>
                                <div>
                                    <div class="vf-control__h3">E-commerce / маркетплейсы</div>
                                    <div class="vf-control__lead">Обороты есть — деньги «гуляют»</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                                <div class="vf-control__icon"><i class="bi bi-briefcase"></i></div>
                                <div>
                                    <div class="vf-control__h3">Сервисный бизнес</div>
                                    <div class="vf-control__lead">Сложно держать маржу и кэш</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                                <div class="vf-control__icon"><i class="bi bi-people"></i></div>
                                <div>
                                    <div class="vf-control__h3">SMB без учёта</div>
                                    <div class="vf-control__lead">Цифры есть, системы — нет</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- SYMPTOMS -->
    <section class="py-5 vf-section-dark">
        <div class="container-xxl vf-anchor" id="symptoms">
            <div class="row g-4">
                <div class="col-lg-5">
                    <h2 class="fw-semibold mb-2">Обычно это звучит так</h2>
                    <p class="vf-muted-on-dark mb-0">
                        Если ловите себя на этих мыслях — это не про «ошибки». Это про отсутствие управленческой картины.
                    </p>
                </div>
                <div class="col-lg-7">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex gap-3 py-2 border-bottom border-white border-opacity-10">
                            <i class="bi bi-dot fs-3"></i>
                            <div>Выручка выросла, а дышать стало тяжелее</div>
                        </li>
                        <li class="d-flex gap-3 py-2 border-bottom border-white border-opacity-10">
                            <i class="bi bi-dot fs-3"></i>
                            <div>Деньги в бизнесе есть, но забирать страшно</div>
                        </li>
                        <li class="d-flex gap-3 py-2 border-bottom border-white border-opacity-10">
                            <i class="bi bi-dot fs-3"></i>
                            <div>Прибыль «на бумаге» есть, а на счетах — напряжение</div>
                        </li>
                        <li class="d-flex gap-3 py-2 border-bottom border-white border-opacity-10">
                            <i class="bi bi-dot fs-3"></i>
                            <div>Каждое решение ощущается как риск</div>
                        </li>
                        <li class="d-flex gap-3 py-2 border-bottom border-white border-opacity-10">
                            <i class="bi bi-dot fs-3"></i>
                            <div>Кассовые разрывы возникают внезапно</div>
                        </li>
                        <li class="d-flex gap-3 py-2">
                            <i class="bi bi-dot fs-3"></i>
                            <div>Нет понимания: это временно или системно</div>
                        </li>
                    </ul>
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

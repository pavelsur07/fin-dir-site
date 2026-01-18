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

    <!-- WHY THIS HAPPENS -->
    <section class="py-5 mb-4 bg-primary-subtle">
        <div class="container-xxl vf-anchor" id="why">
            <div class="vf-control__head">
                <h2 class="section-title vf-control__title">Почему «формально всё нормально», а внутри тревожно</h2>
                <p class="section-subtitle vf-faq__subtitle">
                    Большинство бизнесов живут в разрозненных цифрах. В итоге вы видите цифры, но не понимаете коридор безопасных решений.
                </p>
            </div>

            <div class="row g-4 mt-3">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="vf-control__h3">Бухгалтерия — для отчётности</div>
                            <div class="vf-control__lead">Сходится «по правилам», но не отвечает на вопрос «что можно делать дальше».</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="vf-control__h3">Банк — для остатков</div>
                            <div class="vf-control__lead">Остаток на счёте не равен свободным деньгам и не показывает риски.</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="vf-control__h3">Таблицы — «как получится»</div>
                            <div class="vf-control__lead">Нету единой картины: ДДС, ОПиУ и Баланс не собраны в систему решений.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-light mt-4 mb-0">
                <strong>Итог:</strong> решения принимаются «на ощущениях», потому что нет понятного <strong>коридора допустимых решений</strong>.
            </div>
        </div>
    </section>

    <?php
    // Home CTA: reusable lead capture form with modal (ref section id="lead-form-2")
    get_template_part('template-parts/landing/lead-capture');
    ?>
    <!-- OUTPUT / WHAT YOU GET -->
    <section class="py-5">
        <div class="container-xxl vf-anchor" id="outcome">
            <div class="vf-control__head">
                <h2 class="section-title vf-control__title">С чем вы уйдёте</h2>
                <p class="section-subtitle vf-faq__subtitle">
                    После диагностики у вас появится ясность — что происходит, где риск, и можно ли сейчас принимать решения спокойно.
                </p>
            </div>

            <div class="row g-3 g-lg-4 mt-3">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                                <div class="vf-control__icon"><i class="bi bi-eye"></i></div>
                                <div>
                                    <div class="fw-semibold">Понятная картина</div>
                                    <div class="text-muted small">Не «много цифр», а что они значат для решений.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                        <div class="vf-control__icon"><i class="bi bi-exclamation-circle"></i></div>
                        <div>
                            <div class="fw-semibold">Зоны риска</div>
                            <div class="text-muted small">Что именно мешает принимать решения без тревоги.</div>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                        <div class="vf-control__icon"><i class="bi bi-cash-coin"></i></div>
                        <div>
                            <div class="fw-semibold">Про «забирать деньги»</div>
                            <div class="text-muted small">Понимание: можно ли сейчас и почему.</div>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                        <div class="vf-control__icon"><i class="bi bi-diagram-3"></i></div>
                        <div>
                            <div class="fw-semibold">Система или рост</div>
                            <div class="text-muted small">Проблема в процессах или в масштабе.</div>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                        <div class="vf-control__icon"><i class="bi bi-arrow-right"></i></div>
                        <div>
                            <div class="fw-semibold">Следующий шаг</div>
                            <div class="text-muted small">Без давления, без обязательств.</div>
                        </div>
                    </div>
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

    <?php get_template_part('template-parts/blocks/tariffs-2'); ?>


    <?php get_template_part('template-parts/landing/sections'); ?>
</main>

<?php get_footer(); ?>

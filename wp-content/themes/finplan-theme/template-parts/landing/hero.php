<?php
/**
 * Hero-блок главной ("Ваш ФинДиректор")
 * Данные берём из ACF, с дефолтами, чтобы не сломалось без полей.
 */

$hero_badge      = get_field( 'hero_badge' ) ?: 'Финансовый директор + облачный сервис учёта';
$hero_title      = get_field( 'hero_title' ) ?: 'Берём финансы бизнеса под контроль<br>за 90 дней';
$hero_subtitle   = get_field( 'hero_subtitle' ) ?: 'Настраиваем управленческий учёт, ДДС и платёжный календарь. Вы видите, куда уходят деньги, и заранее знаете о кассовых разрывах.';

$hero_cta_primary_text   = get_field( 'hero_cta_primary_text' ) ?: 'Записаться на диагностику';
$hero_cta_primary_link   = get_field( 'hero_cta_primary_link' ) ?: '#cta-consult';

$hero_cta_secondary_text = get_field( 'hero_cta_secondary_text' ) ?: 'Скачать шаблон ДДС';
$hero_cta_secondary_link = get_field( 'hero_cta_secondary_link' ) ?: '#materials';

$hero_stat1_value = get_field( 'hero_stat1_value' ) ?: '15+ лет';
$hero_stat1_label = get_field( 'hero_stat1_label' ) ?: 'опыта CFO в производстве, торговле и онлайн-бизнесе';

$hero_stat2_value = get_field( 'hero_stat2_value' ) ?: '20+ компаний';
$hero_stat2_label = get_field( 'hero_stat2_label' ) ?: 'выведены из кассовых разрывов в стабильный плюс';
?>

<section id="top" class="page-section">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">

                <?php if ( $hero_badge ) : ?>
                    <div class="badge-accent mb-3">
                        <?php echo wp_kses_post( $hero_badge ); ?>
                    </div>
                <?php endif; ?>

                <h1 class="hero-title mb-3">
                    <?php echo wp_kses_post( $hero_title ); ?>
                </h1>

                <?php if ( $hero_subtitle ) : ?>
                    <p class="hero-subtitle mb-3">
                        <?php echo wp_kses_post( $hero_subtitle ); ?>
                    </p>
                <?php endif; ?>

                <ul class="hero-list">
                    <li>Диагностика текущей финансовой ситуации и рисков</li>
                    <li>Внедрение ДДС и платёжного календаря в нашем сервисе</li>
                    <li>План по росту чистой прибыли на 6–12 месяцев</li>
                </ul>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="<?php echo esc_url( $hero_cta_primary_link ); ?>" class="btn btn-primary">
                        <?php echo esc_html( $hero_cta_primary_text ); ?>
                    </a>
                    <a href="<?php echo esc_url( $hero_cta_secondary_link ); ?>" class="btn btn-outline-primary">
                        <?php echo esc_html( $hero_cta_secondary_text ); ?>
                    </a>
                </div>

                <div class="stats d-flex flex-wrap gap-4 mt-3">
                    <div class="stats-item">
                        <strong><?php echo esc_html( $hero_stat1_value ); ?></strong>
                        <span><?php echo esc_html( $hero_stat1_label ); ?></span>
                    </div>
                    <div class="stats-item">
                        <strong><?php echo esc_html( $hero_stat2_value ); ?></strong>
                        <span><?php echo esc_html( $hero_stat2_label ); ?></span>
                    </div>
                </div>

            </div>

            <div class="col-lg-5 offset-lg-1">
                <div class="card card-soft p-4 bg-white">
                    <h2 class="h5 mb-2">Бесплатная диагностическая сессия</h2>
                    <p class="small text-muted mb-3">
                        Оставьте контакты — за 45–60 минут разберём ваши цифры и покажем,
                        где «утекает» прибыль.
                    </p>

                    <!-- Пока обычная форма; затем можно заменить на CF7 / вашу форму -->
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Имя</label>
                            <input type="text" class="form-control" placeholder="Как к вам обращаться?">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Телефон / Telegram</label>
                            <input type="text" class="form-control" placeholder="+7… или @username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Бизнес</label>
                            <input type="text" class="form-control" placeholder="Отрасль, формат бизнеса">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Средняя выручка в месяц</label>
                            <select class="form-select">
                                <option>до 2 млн ₽</option>
                                <option>2–10 млн ₽</option>
                                <option>10–50 млн ₽</option>
                                <option>50+ млн ₽</option>
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="hero_agree">
                            <label class="form-check-label small" for="hero_agree">
                                Согласен(а) на обработку персональных данных
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Получить диагноз по финансам
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>

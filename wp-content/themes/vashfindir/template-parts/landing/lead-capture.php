<?php
/**
 * Reusable lead capture section (reference: section id="lead-form-2")
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="vf-lead vf-section-pad-lg" id="lead-form-2" aria-labelledby="vfLeadTitle">
    <div class="container">
        <div class="vf-lead__card">
            <div class="vf-lead__grid">
                <div class="vf-lead__content">
                    <h2 class="vf-lead__title" id="vfLeadTitle">Готовы навести порядок в финансах?</h2>
                    <p class="vf-lead__text">
                        Оставьте заявку — и мы свяжемся, чтобы обсудить задачу и подобрать формат:
                        <span>Я веду сам</span> или <span>Доверить финансы профи</span>.
                    </p>
                    <div class="vf-lead__note">
                        Мы ответим в рабочее время. Обычно — в течение 1 рабочего дня.
                    </div>
                    <div class="d-flex gap-2 flex-wrap mt-3">
                        <span class="badge text-bg-light"><i class="bi bi-clock me-1"></i>45 минут</span>
                        <span class="badge text-bg-light"><i class="bi bi-shield-check me-1"></i>без продаж</span>
                        <span class="badge text-bg-light"><i class="bi bi-hand-thumbs-up me-1"></i>без обязательств</span>
                    </div>

                </div>

                <div class="vf-lead__formWrap">
                    <form class="vf-lead__form" action="#" method="post" aria-label="Лид-форма" data-vf-lead-form>
                        <div class="vf-lead__field">
                            <input class="vf-field" type="text" name="name" autocomplete="name" placeholder="Ваше имя" required>
                        </div>
                        <div class="vf-lead__field">
                            <input class="vf-field" type="tel" name="phone" autocomplete="tel" placeholder="+7 ___ ___-__-__" required>
                        </div>
                        <div class="vf-lead__field">
                            <input class="vf-field" type="email" name="email" autocomplete="email" placeholder="E-mail" required>
                        </div>

                        <button class="vf-btn btn btn-cta w-100 vf-btn--block" type="submit">Отправить</button>

                        <label class="vf-consent">
                            <input type="checkbox" name="consent" checked>
                            <span>
                                Даю согласие на обработку персональных данных в соответствии с
                                <a href="#">политикой конфиденциальности</a> и принимаю условия
                                <a href="#">публичной оферты</a>.
                            </span>
                        </label>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal (opens on submit) -->
    <div class="vf-modal" data-vf-modal hidden>
        <div class="vf-modal__backdrop" data-vf-modal-close aria-hidden="true"></div>
        <div class="vf-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="vfLeadModalTitle">
            <button class="vf-modal__close" type="button" aria-label="Закрыть" data-vf-modal-close>×</button>
            <div class="vf-modal__body">
                <div class="vf-modal__title" id="vfLeadModalTitle">Заявка принята</div>
                <div class="vf-modal__text">Мы свяжемся с вами в ближайшее время, чтобы уточнить задачу и предложить формат работы.</div>
            </div>
        </div>
    </div>
</section>

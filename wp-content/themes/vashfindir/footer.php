<footer class="vf-footer" role="contentinfo">
    <div class="vf-footer__inner">
        <div class="vf-footer__grid">

            <div class="vf-footer__col vf-footer__col--brand">
                <div class="vf-footer__brand">Ваш финдир</div>
                <p class="vf-footer__text">
                    Финансовый SaaS + сопровождение. Контроль денег, прогноз и решения — чтобы управлять “до”, а не “после”.
                </p>

                <ul class="vf-footer__bullets">
                    <li>Данные клиентов защищены</li>
                    <li>Роли доступа и журнал действий</li>
                    <li>Поддержка и онбординг</li>
                </ul>
            </div>

            <div class="vf-footer__col">
                <div class="vf-footer__title">Продукт</div>
                <nav class="vf-footer__nav" aria-label="Ссылки продукта">
                    <a class="vf-footer__link" href="<?php echo esc_url(home_url('/service/')); ?>">О сервисе</a>
                    <a class="vf-footer__link" href="<?php echo esc_url(home_url('/service-findir/')); ?>">Сервис + ФинДиректор</a>
                </nav>
            </div>

            <div class="vf-footer__col">
                <div class="vf-footer__title">Компания</div>
                <nav class="vf-footer__nav" aria-label="Ссылки компании">
                    <a class="vf-footer__link" href="<?php echo esc_url(home_url('/about/')); ?>">О нас</a>
                    <!-- Ссылки добавим позже, когда появятся страницы -->
                </nav>
            </div>

            <div class="vf-footer__col">
                <div class="vf-footer__title">Связь</div>

                <div class="vf-footer__kv">
                    <div class="vf-footer__k">Email</div>
                    <a class="vf-footer__linkStrong" href="mailto:hello@vashfindir.ru">hello@vashfindir.ru</a>
                </div>

                <div class="vf-footer__kv">
                    <div class="vf-footer__k">Telegram</div>
                    <a class="vf-footer__linkStrong" href="https://t.me/vashfindir_ru" target="_blank" rel="noopener">vashfindir_ru</a>
                </div>

                <div class="vf-footer__note">Отвечаем в рабочее время.</div>
            </div>
        </div>

        <div class="vf-footer__bottom">
            <div class="vf-footer__meta">
                ИНН/ОГРН/реквизиты — добавим позже. Данные клиентов защищены.
            </div>

            <div class="vf-footer__legal">
                <!-- Позже заменим на реальные страницы -->
                <span class="vf-footer__linkSoft">Политика конфиденциальности</span>
                <span class="vf-footer__dot">·</span>
                <span class="vf-footer__linkSoft">Оферта</span>
                <span class="vf-footer__dot">·</span>
                <span class="vf-footer__copy">© <?php echo esc_html(date('Y')); ?></span>
            </div>
        </div>
    </div>
</footer>

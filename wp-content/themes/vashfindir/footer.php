<?php
/**
 * Footer
 */
?>

<footer class="vf-footer" role="contentinfo">
    <div class="vf-footer__inner">
        <div class="vf-footer__grid">

            <div class="vf-footer__col vf-footer__col--brand">
                <a class="vf-footer__logo-link" href="<?= esc_url(home_url('/')) ?>" aria-label="Ваш финдир — на главную">
                    <img class="vf-footer__logo"
                         src="<?= esc_url(get_theme_file_uri('assets/img/logo-vf-on-dark.png')) ?>"
                         width="360" height="94"
                         alt="Ваш финдир"
                         decoding="async"
                         loading="lazy">
                </a>
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

                <div class="vf-social" aria-label="Социальные сети">
                    <a class="vf-social__item" href="#" aria-label="Telegram">
                        <svg class="vf-social__icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M21.7 3.5c.6-.25 1.2.28 1 .92l-3.4 16.4c-.18.85-1.05 1.2-1.78.8l-5.1-2.98-2.46 2.37c-.28.27-.76.12-.83-.26l-.01-.05.37-5.24L18.6 6.6c.32-.3-.06-.76-.44-.52L6.3 13.2l-4.9-1.54c-.72-.23-.75-1.22-.04-1.5L21.7 3.5Z"/>
                        </svg>
                    </a>

                    <a class="vf-social__item" href="#" aria-label="ВКонтакте">
                        <svg class="vf-social__icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3.3 7.2c.1 5.5 2.9 9.3 7.7 9.3h.4v-3.1c1.7.17 3 1.4 3.5 3.1h2.5c-.64-2.3-2.6-3.7-4-4.2 1.4-.8 3-2.4 3.5-5.1h-2.3c-.62 2.1-2.1 3.8-3.2 4V7.2h-2.3v7c-1.1-.3-2.7-2.1-2.8-7H3.3Z"/>
                        </svg>
                    </a>

                    <a class="vf-social__item" href="#" aria-label="YouTube">
                        <svg class="vf-social__icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M21.6 7.2a3 3 0 0 0-2.1-2.1C17.7 4.6 12 4.6 12 4.6s-5.7 0-7.5.5A3 3 0 0 0 2.4 7.2 31.4 31.4 0 0 0 2 12a31.4 31.4 0 0 0 .4 4.8 3 3 0 0 0 2.1 2.1c1.8.5 7.5.5 7.5.5s5.7 0 7.5-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 22 12a31.4 31.4 0 0 0-.4-4.8ZM10 15.5v-7l6 3.5-6 3.5Z"/>
                        </svg>
                    </a>

                    <a class="vf-social__item" href="#" aria-label="Instagram">
                        <svg class="vf-social__icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7.2 2h9.6A5.2 5.2 0 0 1 22 7.2v9.6A5.2 5.2 0 0 1 16.8 22H7.2A5.2 5.2 0 0 1 2 16.8V7.2A5.2 5.2 0 0 1 7.2 2Zm9.6 2H7.2A3.2 3.2 0 0 0 4 7.2v9.6A3.2 3.2 0 0 0 7.2 20h9.6a3.2 3.2 0 0 0 3.2-3.2V7.2A3.2 3.2 0 0 0 16.8 4ZM12 7.3A4.7 4.7 0 1 1 7.3 12 4.7 4.7 0 0 1 12 7.3Zm0 2A2.7 2.7 0 1 0 14.7 12 2.7 2.7 0 0 0 12 9.3Zm5.2-2.5a1.1 1.1 0 1 1-1.1 1.1 1.1 0 0 1 1.1-1.1Z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="vf-footer__bottom">
            <div class="vf-footer__meta">
                ИНН:6140000234 / ОГРН: 1156188000176 Данные клиентов защищены.
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

</div><!-- /.site-container -->

<?php wp_footer(); ?>
</body>
</html>

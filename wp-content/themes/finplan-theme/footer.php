<footer class="site-footer border-top mt-4 pt-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="d-flex align-items-center gap-2">
                    <img
                            src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-vashfindir.svg' ); ?>"
                            alt="Ваш ФинДиректор"
                            style="height: 32px;"
                    >
                    <span>Ваш ФинДиректор</span>
                </div>
                <div class="mt-2 small">
                    Финансовый директор на аутсорсе и сервис управления деньгами.
                </div>
            </div>

            <div class="col-md-6 text-md-end small">
                <div>Телеграм: @your_cfo_bot</div>
                <div>E-mail: hello@vashfindir.ru</div>
                <div class="mt-1">
                    © <?php echo date('Y'); ?> Ваш ФинДиректор
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <div class="footer-links d-flex flex-wrap justify-content-center gap-3 small">
                    <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" class="text-muted text-decoration-none">
                        Политика конфиденциальности
                    </a>
                    <a href="<?php echo esc_url( home_url( '/dogovor-oferty/' ) ); ?>" class="text-muted text-decoration-none">
                        Договор оферты
                    </a>
                    <a href="<?php echo esc_url( home_url( '/personal-data-consent/' ) ); ?>" class="text-muted text-decoration-none">
                        Согласие на обработку персональных данных
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<script async src="https://app.conwix.ru/webchat/widget.js"
        data-site-key="kj18AUv2ZEEe-CIyAZqnV2my"
        data-api-base="https://app.conwix.ru">
</script>

<?php wp_footer(); ?>
</body>
</html>

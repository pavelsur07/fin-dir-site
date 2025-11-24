<?php
/**
 * Template Name: Лендинг для партнёров
 * Template Post Type: page
 */

get_header(); ?>

    <main class="page-landing-partners">
        <section class="hero-partners">
            <div class="container">
                <p class="eyebrow">Партнёрам</p>
                <h1>Партнёрская программа «Ваш ФинДиректор»</h1>
                <p class="lead">
                    Зарабатывайте вместе с нами, рекомендуя услуги финансового директора
                    своим клиентам и партнёрам.
                </p>
                <a href="#partner-form" class="btn btn-primary">
                    Стать партнёром
                </a>
            </div>
        </section>

        <section class="partners-benefits">
            <div class="container">
                <h2>Что вы получаете</h2>
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <h3>Регулярный доход</h3>
                        <p>Процент от оплаты каждого клиента, которого вы привели.</p>
                    </div>
                    <div class="benefit-card">
                        <h3>Прозрачная отчётность</h3>
                        <p>Ежемесячные отчёты по вашим клиентам и начислениям.</p>
                    </div>
                    <div class="benefit-card">
                        <h3>Поддержка маркетинга</h3>
                        <p>Готовые материалы: презентации, лендинги, скрипты.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="partner-form" class="partners-form">
            <div class="container">
                <h2>Оставьте заявку на партнёрство</h2>
                <p>Мы свяжемся с вами, уточним детали и подключим к программе.</p>

                <?php
                // Здесь можешь вывести шорткод формы, например Contact Form 7 или свою форму:
                // echo do_shortcode('[contact-form-7 id="123" title="Партнёрская форма"]');
                ?>
            </div>
        </section>
    </main>

<?php get_footer();

<?php
get_header();
?>

    <main>
        <section class="hero">
            <div>
                <h1 class="hero-title">
                    Возьми деньги бизнеса под контроль
                </h1>
                <p class="hero-subtitle">
                    FinDir помогает владельцам e-commerce и селлерам видеть ДДС, P&L и кассовые разрывы
                    в одном кабинете — без сложных таблиц и ручной рутины.
                </p>
                <a href="#demo" class="btn-primary">Получить бесплатный разбор ДДС</a>
            </div>
            <div class="hero-card">
                <h3 style="margin-top:0;">Что увидите на демо</h3>
                <ul>
                    <li>Деньги по банкам, кассам, кошелькам и маркетплейсам</li>
                    <li>Сводный ДДС по компаниям и проектам</li>
                    <li>Платежный календарь и прогноз кассовых разрывов</li>
                </ul>
                <p style="font-size:13px;color:#7a7f96;margin-bottom:0;">
                    30–40 минут, без навязчивых продаж.
                </p>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">Кому полезен FinDir</h2>
            <div class="cards">
                <div class="card">
                    <strong>Селлерам маркетплейсов</strong>
                    <p>Сводим отчёты WB/Ozon, показываем реальную маржу и прибыль по товарам.</p>
                </div>
                <div class="card">
                    <strong>E-commerce и D2C</strong>
                    <p>Объединяем банки, кассы, рекламу и склады в один финансовый контур.</p>
                </div>
                <div class="card">
                    <strong>Сервисным бизнесам</strong>
                    <p>Платежный календарь и контроль обязательств перед сотрудниками и подрядчиками.</p>
                </div>
            </div>
        </section>

        <section id="demo" class="section">
            <h2 class="section-title">Запросить бесплатный разбор</h2>
            <p>Оставьте контакты, мы свяжемся и уточним, какие задачи по учёту сейчас приоритетны.</p>
            <!-- Временно простая форма, потом заменишь на интеграцию -->
            <form method="post">
                <p><input type="text" name="name" placeholder="Имя" required></p>
                <p><input type="email" name="email" placeholder="E-mail" required></p>
                <p><input type="text" name="company" placeholder="Компания / ниша"></p>
                <p><button class="btn-primary" type="submit">Отправить</button></p>
            </form>
        </section>
    </main>

<?php
get_footer();

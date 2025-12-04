<?php
/*
Template Name: Фин-сервис для собственника
*/
get_header();
?>

    <main class="site-main">

        <!-- ЭКРАН 1: Hero фин-сервиса -->
        <section id="top" class="page-section">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6">
                        <div class="badge-accent mb-3">
                            Облачный фин-сервис для собственника
                        </div>
                        <h1 class="hero-title mb-3">
                            Деньги, прибыль и обязательства<br>в одном фин-сервисе
                        </h1>
                        <p class="hero-subtitle mb-3">
                            Ваш ФинДир — облачный сервис, в котором собственник видит ДДС,
                            платёжный календарь, прибыль и обязательства в одном окне.
                            Сервис можно использовать отдельно от услуг финдиректора.
                        </p>
                        <ul class="hero-list">
                            <li>Деньги на счетах и в кассе — в реальном времени</li>
                            <li>Платёжный календарь и прогноз кассовых разрывов</li>
                            <li>Управленческий P&amp;L и обязательства по кредитам и налогам</li>
                        </ul>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="#demo" class="btn btn-primary">
                                Запросить демо фин-сервиса
                            </a>
                            <a href="#modules" class="btn btn-outline-primary">
                                Посмотреть, что внутри
                            </a>
                        </div>
                        <div class="stats">
                            <div class="stats-item">
                                <strong>15+ лет</strong>
                                <span>опыта CFO в малом и среднем бизнесе</span>
                            </div>
                            <div class="stats-item">
                                <strong>20+ компаний</strong>
                                <span>переведены из хаоса в прогнозируемый кэшфлоу</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 offset-lg-1">
                        <div class="card card-soft p-4 bg-white">
                            <h2 class="h5 mb-2">Как выглядит фин-сервис</h2>
                            <p class="small text-muted mb-3">
                                В реальности вы видите не «отчёт на 20 страниц», а 1–2 экрана
                                с ключевыми показателями и платежами.
                            </p>

                            <!-- HERO-ФОТО: собственник + сервис -->
                            <img
                                src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/finservice-hero.png' ); ?>"
                                class="img-fluid rounded-3 mb-3"
                                alt="Собственник бизнеса работает с облачным фин-сервисом Ваш ФинДир"
                            >

                            <div class="mb-2">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Деньги на счетах и в кассе</span>
                                    <span class="fw-semibold">12 480 000 ₽</span>
                                </div>
                                <div class="progress rounded-pill" style="height: 6px;">
                                    <div class="progress-bar" style="width: 65%;"></div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mb-3">
                                <div class="flex-fill border rounded-3 p-2">
                                    <p class="small text-muted mb-1">Прибыль месяца</p>
                                    <p class="mb-0" style="font-size: 0.9rem;">+ 1 280 000 ₽</p>
                                </div>
                                <div class="flex-fill border rounded-3 p-2">
                                    <p class="small text-muted mb-1">Обязательства 30 дней</p>
                                    <p class="mb-0" style="font-size: 0.9rem;">7 950 000 ₽</p>
                                </div>
                            </div>
                            <p class="small text-muted mb-0">
                                На демо мы покажем такой же дашборд, но уже на ваших цифрах.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ЭКРАН 2: Боли без сервиса -->
        <section class="page-section section-muted">
            <div class="container">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-6">
                        <h2 class="section-title">Когда финансы живут в Excel и 1С</h2>
                        <p class="section-subtitle">
                            Собственник видит только «остаток на счёте» и отчёт раз в месяц,
                            а решения приходится принимать вслепую.
                        </p>
                    </div>
                    <div class="col-lg-6">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card card-soft h-100 p-3">
                                    <h3 class="h6 mb-2">Разрозненные файлы</h3>
                                    <p class="small text-muted mb-0">
                                        ДДС в одной таблице, P&amp;L в другой, кредиты — в заметках.
                                        На простой вопрос «сколько денег?» нет быстрого ответа.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-soft h-100 p-3">
                                    <h3 class="h6 mb-2">Кассовые разрывы «вдруг»</h3>
                                    <p class="small text-muted mb-0">
                                        Налоги, аренда, кредиты и поставщики накладываются, и
                                        дефицит денег становится заметен только в момент платежа.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-soft h-100 p-3">
                                    <h3 class="h6 mb-2">Прибыль есть на бумаге, но не на счетах</h3>
                                    <p class="small text-muted mb-0">
                                        Нет связки между ДДС и P&amp;L — непонятно, какие направления
                                        зарабатывают, а какие сжигают деньги и время команды.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-soft h-100 p-3">
                                    <h3 class="h6 mb-2">Решения «на глаз»</h3>
                                    <p class="small text-muted mb-0">
                                        Расширяться или нет, нанимать или подождать — всё решается
                                        по ощущениям, а не по цифрам в одном окне.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ЭКРАН 3: Что такое фин-сервис -->
        <section class="page-section" id="about-service">
            <div class="container">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-6">
                        <h2 class="section-title">Что такое фин-сервис Ваш ФинДир</h2>
                        <p class="section-subtitle">
                            Это облачный сервис, который собирает движение денег, прибыль
                            и обязательства в одном окне. Без «финансовой магии» и громоздких отчётов.
                        </p>
                        <ul class="hero-list mb-3">
                            <li>ДДС и платёжный календарь с прогнозом на 7–30 дней</li>
                            <li>Управленческий P&amp;L по компаниям, направлениям и каналам</li>
                            <li>Учёт кредитов, налогов и других обязательств по периодам</li>
                            <li>Отчёты для собственника на 1–2 экрана, а не на 20 листов</li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-3">Два варианта использования</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <p class="small text-muted mb-1">Вариант 1</p>
                                        <p class="fw-semibold mb-2">Только сервис</p>
                                        <p class="small text-muted mb-0">
                                            Вы и команда используете сервис самостоятельно:
                                            видите деньги, платежи и прибыль. Подходит, если уже есть
                                            бухгалтер или аутсорс.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <p class="small text-muted mb-1">Вариант 2</p>
                                        <p class="fw-semibold mb-2">Сервис + финдиректор</p>
                                        <p class="small text-muted mb-0">
                                            Мы подключаемся как финдиректор: настраиваем учёт,
                                            комментируем цифры, помогаем принимать решения.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <p class="small text-muted mt-3 mb-0">
                                Можно начать с сервиса отдельно и перейти к формату
                                «сервис + финдиректор», когда понадобится более глубокое сопровождение.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ЭКРАН 4: Ключевые модули -->
        <section id="modules" class="page-section section-muted">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-lg-7">
                        <h2 class="section-title">Что вы получаете внутри сервиса</h2>
                        <p class="section-subtitle">
                            Модули фин-сервиса собраны вокруг задач собственника: видеть деньги,
                            управлять обязательствами и понимать реальную прибыль.
                        </p>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-2">Платёжный календарь</h3>
                            <p class="small text-muted mb-0">
                                Все будущие платежи по дням и неделям: налоги, кредиты, аренда,
                                поставщики, зарплаты. Видно, когда и на какую сумму нужен кэш.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-2">ДДС по статьям</h3>
                            <p class="small text-muted mb-0">
                                Разбивка движений денег: товар, реклама, ФОТ, налоги, прочее.
                                Легко заметить «пылесосы» денег и лишние расходы.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-2">Управленческий P&amp;L</h3>
                            <p class="small text-muted mb-0">
                                Прибыль по месяцам и направлениям: видно, какие продукты и каналы
                                зарабатывают, а какие стоит отключить или перестроить.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-2">Кредиты и займы</h3>
                            <p class="small text-muted mb-0">
                                Отдельный учёт тела и процентов по каждому кредиту. Нагрузка
                                на кэшфлоу по месяцам — без сюрпризов.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-2">Фонды и резервы</h3>
                            <p class="small text-muted mb-0">
                                Отдельные «копилки» на налоги, развитие, дивиденды, резерв.
                                Деньги на важное не смешиваются с ежедневными расходами.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-2">Маркетплейсы и онлайн-торговля</h3>
                            <p class="small text-muted mb-0">
                                Продажи, выкуп, комиссии и логистика по маркетплейсам стягиваются
                                в один отчёт и попадают в общую картину прибыли.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ЭКРАН 5: Одна страница для собственника (с фото дашборда) -->
        <section class="page-section">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6">
                        <h2 class="section-title">Одна страница, на которую вы смотрите каждый день</h2>
                        <p class="section-subtitle">
                            Главный экран фин-сервиса построен вокруг вопросов собственника,
                            а не бухгалтера.
                        </p>
                        <ul class="hero-list mb-3">
                            <li>Сколько денег есть сегодня и сколько будет через 2–4 недели?</li>
                            <li>Какую прибыль даёт бизнес в этом месяце?</li>
                            <li>Какой объём обязательств у нас по кредитам, налогам и поставщикам?</li>
                            <li>Сколько денег можно безопасно забрать из бизнеса?</li>
                        </ul>
                        <a href="#demo" class="btn btn-primary">
                            Посмотреть демо на своих цифрах
                        </a>
                    </div>
                    <div class="col-lg-6">
                        <div class="card card-soft p-4">
                            <!-- КРУПНЫЙ ДАШБОРД -->
                            <img
                                src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/finservice-dashboard.png' ); ?>"
                                class="img-fluid rounded-3 mb-3"
                                alt="Главный дашборд фин-сервиса Ваш ФинДир с ключевыми показателями"
                            >
                            <p class="small text-muted mb-2">
                                На реальном экране вы видите: деньги, платежи, прибыль, обязательства
                                и фонды — в одном дашборде.
                            </p>
                            <ul class="small text-muted mb-0">
                                <li>• Верхний блок: деньги на счетах и свободный остаток</li>
                                <li>• График ДДС и кассовых разрывов на 30 дней</li>
                                <li>• Плитки: прибыль месяца, обязательства, фонды и резервы</li>
                                <li>• Алерты: риск дефицита и перегрузка по кредитам</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ЭКРАН 6: Интеграции (с инфографикой) -->
        <section class="page-section section-muted">
            <div class="container">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-6">
                        <h2 class="section-title">Интеграции и автоматизация</h2>
                        <p class="section-subtitle">
                            Сервис не требует ручного переписывания всех данных. Мы подтягиваем
                            информацию из тех систем, где вы уже живёте.
                        </p>
                        <ul class="hero-list mb-0">
                            <li>Банковские выписки (API или загрузка)</li>
                            <li>1С, МойСклад и другие учётные системы</li>
                            <li>Маркетплейсы: Wildberries, Ozon и др.</li>
                            <li>Онлайн-кассы и интернет-магазины</li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <div class="card card-soft p-4 h-100">
                            <!-- СХЕМА ИНТЕГРАЦИЙ -->
                            <img
                                src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/finservice-integrations.png' ); ?>"
                                class="img-fluid rounded-3 mb-3"
                                alt="Схема интеграций фин-сервиса Ваш ФинДир с банками, 1С и маркетплейсами"
                            >
                            <p class="small text-muted mb-0">
                                В центре — фин-сервис, вокруг — банки, учёт, маркетплейсы и кассы.
                                Все данные стекаются в одну систему, а собственник видит уже собранную картину.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ЭКРАН 7: Безопасность (с фото) -->
        <section class="page-section">
            <div class="container">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-5">
                        <!-- ФОТО БЕЗОПАСНОСТИ -->
                        <img
                            src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/finservice-security.png' ); ?>"
                            class="img-fluid rounded-3 mb-3"
                            alt="Безопасное хранение финансовых данных в облаке Ваш ФинДир"
                        >
                        <h2 class="section-title">Безопасность и права доступа</h2>
                        <p class="section-subtitle">
                            Финансовые данные — чувствительная информация. Мы уделяем внимание тому,
                            кто и что видит внутри сервиса.
                        </p>
                    </div>
                    <div class="col-lg-7">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card card-soft h-100 p-3">
                                    <h3 class="h6 mb-2">Разграничение ролей</h3>
                                    <p class="small text-muted mb-0">
                                        Собственник, финдиректор, бухгалтер, менеджеры —
                                        у каждого свой уровень доступа к компаниям и отчётам.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-soft h-100 p-3">
                                    <h3 class="h6 mb-2">Резервное копирование</h3>
                                    <p class="small text-muted mb-0">
                                        Регулярные бэкапы и логирование ключевых действий
                                        помогают избежать потери данных и ошибок.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-soft h-100 p-3">
                                    <h3 class="h6 mb-2">Прозрачность изменений</h3>
                                    <p class="small text-muted mb-0">
                                        Важно понимать, кто и что менял в данных.
                                        Сервис позволяет отслеживать ключевые корректировки.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-soft h-100 p-3">
                                    <h3 class="h6 mb-2">Доступ из любой точки</h3>
                                    <p class="small text-muted mb-0">
                                        Облачный формат — можно работать с цифрами из офиса, дома
                                        или командировок, не разворачивая тяжёлые системы.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ЭКРАН 8: Внедрение -->
        <section class="page-section section-muted">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-lg-7">
                        <h2 class="section-title">Как проходит внедрение фин-сервиса</h2>
                        <p class="section-subtitle">
                            Мы не делаем из внедрения IT-проект на полгода. Задача — как можно
                            быстрее дать вам рабочий инструмент.
                        </p>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-1">Шаг 1. Диагностика</h3>
                            <p class="small text-muted mb-0">
                                45–60 минут: разбираем текущие отчёты, источники данных и ваши
                                задачи по управлению деньгами и прибылью.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-1">Шаг 2. Подключение и настройка</h3>
                            <p class="small text-muted mb-0">
                                Подключаем банки и учётные системы, настраиваем статьи ДДС и P&amp;L
                                под ваш бизнес, запускаем платёжный календарь.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-1">Шаг 3. Онбординг команды</h3>
                            <p class="small text-muted mb-0">
                                Показываем, на какие отчёты смотреть и какие решения можно
                                принимать уже в первый месяц работы с сервисом.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ЭКРАН 9: Форматы работы с сервисом -->
        <section class="page-section">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-lg-7">
                        <h2 class="section-title">Форматы работы с фин-сервисом</h2>
                        <p class="section-subtitle">
                            Можно начать только с облачного сервиса, а затем подключить
                            финдиректора, когда будет запрос на более глубокое управление.
                        </p>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-1">Только фин-сервис</h3>
                            <p class="small text-muted mb-2">
                                Для собственников, которые готовы работать с цифрами сами
                                или с текущим бухгалтером.
                            </p>
                            <p class="small text-muted mb-0">
                                Доступ ко всем ключевым модулям, 1–2 компании внутри сервиса,
                                поддержка по e-mail / Telegram.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4 border border-2 border-primary">
                            <h3 class="h6 mb-1">Сервис + финдиректор</h3>
                            <p class="small text-muted mb-2">
                                Для бизнеса, которому нужен не только инструмент, но и партнёр
                                по финансовым решениям.
                            </p>
                            <p class="small text-muted mb-0">
                                Регулярные созвоны, планирование, анализ P&amp;L, ДДС и обязательств.
                                Индивидуальный план роста чистой прибыли.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-soft h-100 p-4">
                            <h3 class="h6 mb-1">Пилотный период</h3>
                            <p class="small text-muted mb-2">
                                Тестовый запуск на 1–3 месяца с ограниченным набором функций.
                            </p>
                            <p class="small text-muted mb-0">
                                Подходит, если хотите попробовать фин-сервис на одном направлении
                                бизнеса или отдельной компании.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ЭКРАН 10: FAQ + форма демо с фото -->
        <section id="demo" class="page-section section-muted">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-6">
                        <h2 class="section-title">Ответы на частые вопросы</h2>
                        <div class="mb-3">
                            <h3 class="h6 mb-1">
                                Подойдёт ли сервис, если у нас уже есть бухгалтер и 1С?
                            </h3>
                            <p class="small text-muted mb-0">
                                Да. Сервис не заменяет бухгалтерию, а даёт управленческий взгляд:
                                деньги, обязательства и прибыль в одной системе для собственника.
                            </p>
                        </div>
                        <div class="mb-3">
                            <h3 class="h6 mb-1">
                                Можно ли вести несколько компаний и направлений?
                            </h3>
                            <p class="small text-muted mb-0">
                                Да, сервис рассчитан на несколько юрлиц и направлений внутри
                                одного кабинета собственника.
                            </p>
                        </div>
                        <div class="mb-3">
                            <h3 class="h6 mb-1">
                                Сколько времени занимает старт?
                            </h3>
                            <p class="small text-muted mb-0">
                                Обычно первые отчёты и платёжный календарь вы видите в течение
                                7–14 дней после старта работы.
                            </p>
                        </div>
                        <div class="mb-3">
                            <h3 class="h6 mb-1">
                                Можно ли отказаться от сервиса, если не зайдёт?
                            </h3>
                            <p class="small text-muted mb-0">
                                Да, формат гибкий: можно остановить подписку и выгрузить важные
                                данные перед завершением работы.
                            </p>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card card-soft h-100 p-4 bg-white">
                            <!-- ФОТО ОНЛАЙН-ВСТРЕЧИ / ФОРМЫ -->
                            <img
                                src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/finservice-form.png' ); ?>"
                                class="img-fluid rounded-3 mb-3"
                                alt="Онлайн-встреча с финансовым директором сервиса Ваш ФинДир"
                            >
                            <h3 class="h6 mb-2">Оставьте контакты — покажем сервис на ваших цифрах</h3>
                            <p class="small text-muted mb-3">
                                За 45–60 минут разберём текущую ситуацию и покажем, как фин-сервис
                                поможет навести порядок в деньгах и прибыли.
                            </p>

                            <!-- Здесь можешь заменить на Conwix-форму по аналогии с главной -->
                            <form action="#" method="post" class="finservice-form">
                                <div class="mb-3">
                                    <label class="form-label small" for="fs-name">Имя*</label>
                                    <input
                                        id="fs-name"
                                        class="form-control"
                                        type="text"
                                        name="name"
                                        placeholder="Как к вам обращаться?"
                                        required
                                    >
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small" for="fs-phone">Телефон / Telegram*</label>
                                    <input
                                        id="fs-phone"
                                        class="form-control"
                                        type="text"
                                        name="phone"
                                        placeholder="+7… или @username"
                                        required
                                    >
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small" for="fs-business">Сфера бизнеса*</label>
                                    <input
                                        id="fs-business"
                                        class="form-control"
                                        type="text"
                                        name="business"
                                        placeholder="Производство, торговля, услуги…"
                                        required
                                    >
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small" for="fs-revenue">Средняя выручка в месяц</label>
                                    <select id="fs-revenue" name="revenue" class="form-select">
                                        <option value="">Выберите диапазон</option>
                                        <option value="до 2 млн ₽">до 2 млн ₽</option>
                                        <option value="2–10 млн ₽">2–10 млн ₽</option>
                                        <option value="10–50 млн ₽">10–50 млн ₽</option>
                                        <option value="50+ млн ₽">50+ млн ₽</option>
                                    </select>
                                </div>
                                <div class="form-check mb-3">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="fs-agree"
                                        name="agree"
                                        required
                                    >
                                    <label class="form-check-label small" for="fs-agree">
                                        Согласен(а) на обработку персональных данных
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    Получить демо фин-сервиса
                                </button>
                                <p class="small text-muted mt-2 mb-0">
                                    + чек-лист «Финансовый контроль в 10 показателях» в подарок.
                                </p>
                            </form>
                            <!-- /форма -->
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

<?php
get_footer();

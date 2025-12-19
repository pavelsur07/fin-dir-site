<?php
/* Template Name: New UI (Preview) */

get_header();
?>

<main id="new-ui-app">

<!-- Навигация -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#top">
            <span class="brand-logo-badge">ВФ</span>
            <span class="brand-logo-text">Ваш ФинДиректор</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="mainNav" class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="#services">Услуги</a></li>
                <li class="nav-item"><a class="nav-link" href="#results">Результаты</a></li>
                <li class="nav-item"><a class="nav-link" href="#industries">Отрасли</a></li>
                <li class="nav-item"><a class="nav-link" href="#materials">Материалы</a></li>
                <li class="nav-item">
                    <a class="btn btn-primary ms-lg-3 mt-2 mt-lg-0" href="#cta-consult">Бесплатная диагностика</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ЭКРАН 1: Hero + форма -->
<section id="top" class="page-section">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <div class="badge-accent mb-3">
                    Финансовый директор + облачный сервис учёта
                </div>
                <h1 class="hero-title mb-3">
                    Берём финансы бизнеса под контроль<br>за 90 дней
                </h1>
                <p class="hero-subtitle mb-3">
                    Настраиваем управленческий учёт, ДДС и платёжный календарь. Вы видите,
                    куда уходят деньги, и заранее знаете о кассовых разрывах.
                </p>
                <ul class="hero-list">
                    <li>Диагностика текущей финансовой ситуации и рисков</li>
                    <li>Внедрение ДДС и платёжного календаря в нашем сервисе</li>
                    <li>План по росту чистой прибыли на 6–12 месяцев</li>
                </ul>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="#cta-consult" class="btn btn-primary">
                        Записаться на диагностику
                    </a>
                    <a href="#materials" class="btn btn-outline-primary">
                        Скачать шаблон ДДС
                    </a>
                </div>
                <div class="stats">
                    <div class="stats-item">
                        <strong>15+ лет</strong>
                        <span>опыта CFO в производстве, торговле и онлайн-бизнесе</span>
                    </div>
                    <div class="stats-item">
                        <strong>20+ компаний</strong>
                        <span>выведены из кассовых разрывов в стабильный плюс</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 offset-lg-1">
                <div class="card card-soft p-4">
                    <h2 class="h5 mb-2">Бесплатная диагностическая сессия</h2>
                    <p class="small text-muted mb-3">
                        Оставьте контакты — за 45–60 минут разберём ваши цифры и покажем,
                        где «утекает» прибыль.
                    </p>
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
                            <input class="form-check-input" type="checkbox" id="agree">
                            <label class="form-check-label small" for="agree">
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

<!-- ЭКРАН 2: Основные услуги / Форматы -->
<section id="services" class="page-section section-muted">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-7">
                <h2 class="section-title">Что мы делаем как Ваш ФинДиректор</h2>
                <p class="section-subtitle">
                    Не просто закрываем отчётность, а помогаем собственнику управлять
                    деньгами и прибылью: от ДДС до стратегических решений.
                </p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-soft h-100 p-4">
                    <h3 class="h5 mb-2">Финдиректор на аутсорсе</h3>
                    <p class="small text-muted mb-3">
                        Ежемесячное сопровождение: планирование ДДС, контроль факта,
                        платёжный календарь, отчёты для собственника.
                    </p>
                    <ul class="small mb-3">
                        <li>План-факт по деньгам и прибыли</li>
                        <li>Совместное принятие финансовых решений</li>
                        <li>Регулярные фин-встречи с собственником</li>
                    </ul>
                    <span class="badge bg-light text-dark">Для бизнеса от 2 млн ₽/мес</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-soft h-100 p-4">
                    <h3 class="h5 mb-2">Диагностика и настройка ДДС</h3>
                    <p class="small text-muted mb-3">
                        За 10–14 дней наводим порядок в движении денег, выделяем статьи,
                        показываем, где теряется прибыль.
                    </p>
                    <ul class="small mb-3">
                        <li>Карта потоков денег по бизнесу</li>
                        <li>Сценарий: как закрывать кассовые разрывы</li>
                        <li>Подготовка к внедрению платёжного календаря</li>
                    </ul>
                    <span class="badge bg-light text-dark">Фиксированная стоимость</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-soft h-100 p-4">
                    <h3 class="h5 mb-2">Фин-сервис для собственника</h3>
                    <p class="small text-muted mb-3">
                        Облачный сервис, в котором в одном окне видно счета, ДДС,
                        платёжный календарь и ключевые отчёты.
                    </p>
                    <ul class="small mb-3">
                        <li>Банки, касса, онлайн-кошельки и даже крипта</li>
                        <li>Автоправила распределения по статьям</li>
                        <li>Отчёты в сервисе и в Google-таблицах</li>
                    </ul>
                    <span class="badge bg-light text-dark">Подключается в рамках сопровождения</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ЭКРАН 3: Выгоды для собственника -->
<section class="page-section">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-7">
                <h2 class="section-title">Какие задачи собственника мы закрываем</h2>
                <p class="section-subtitle">
                    Финансы — не про «ведомости», а про спокойную голову собственника:
                    понятные цифры, предсказуемость и управляемый рост.
                </p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <h3 class="h6">Деньги под контролем</h3>
                <p class="small text-muted">
                    Понимание, сколько денег есть сегодня и сколько будет через
                    неделю/месяц с учётом всех обязательств.
                </p>
            </div>
            <div class="col-md-3 col-6">
                <h3 class="h6">Кассовые разрывы — под управлением</h3>
                <p class="small text-muted">
                    Платёжный календарь показывает дефицит заранее — есть время
                    договориться, перенести, привлечь финансирование.
                </p>
            </div>
            <div class="col-md-3 col-6">
                <h3 class="h6">Понимание реальной прибыли</h3>
                <p class="small text-muted">
                    Видно, какие направления и продукты зарабатывают, а какие сжигают
                    деньги и время команды.
                </p>
            </div>
            <div class="col-md-3 col-6">
                <h3 class="h6">Решения на основе цифр</h3>
                <p class="small text-muted">
                    Обоснованные ответы на вопросы: «нанимать ли людей», «открывать ли
                    новую точку», «идти ли в маркетплейсы».
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ЭКРАН 4: Отрасли -->
<section id="industries" class="page-section section-muted">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-7">
                <h2 class="section-title">Понимаем специфику вашего бизнеса</h2>
                <p class="section-subtitle">
                    Работали с производством, торговлей, строительством и онлайн-торговлей.
                    Учитываем особенности ценообразования, складов и длинного цикла сделки.
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-10">
                <div class="pill-list d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-light text-dark">Производство</span>
                    <span class="badge bg-light text-dark">Оптовая и розничная торговля</span>
                    <span class="badge bg-light text-dark">Продажа автотехники</span>
                    <span class="badge bg-light text-dark">Строительство и девелопмент</span>
                    <span class="badge bg-light text-dark">Онлайн-торговля и маркетплейсы</span>
                    <span class="badge bg-light text-dark">Услуги и агентства</span>
                </div>
                <p class="small text-muted">
                    На старте обсуждаем, как устроен ваш бизнес: маржинальность,
                    циклы оплат, сезонность. Встраиваем управленческий учёт в
                    реальную операционку, а не в «идеальную картинку».
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ЭКРАН 5: Кейсы / результаты -->
<section id="results" class="page-section">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-7">
                <h2 class="section-title">Реальные истории клиентов</h2>
                <p class="section-subtitle">
                    Мы не публикуем конфиденциальные цифры, но делимся логикой, за счёт чего
                    выросла прибыль и исчезли постоянные кассовые разрывы.
                </p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-soft h-100 p-4">
                    <div class="case-tag mb-2">Производство</div>
                    <h3 class="h6 mb-2">
                        Из «дырок» по зарплате — к резерву в 2 ФОТ за полгода
                    </h3>
                    <ul class="small text-muted mb-0">
                        <li>Внедрили ДДС и платёжный календарь</li>
                        <li>Пересобрали график оплат поставщикам</li>
                        <li>Создали резервный фонд на 2 месяца расходов</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-soft h-100 p-4">
                    <div class="case-tag mb-2">Онлайн-торговля</div>
                    <h3 class="h6 mb-2">
                        E-commerce из «работаем в ноль» к +18% чистой прибыли
                    </h3>
                    <ul class="small text-muted mb-0">
                        <li>Разделили ДДС по каналам продаж</li>
                        <li>Отказались от нерентабельных SKU</li>
                        <li>Оптимизировали рекламный бюджет по ROMI</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-soft h-100 p-4">
                    <div class="case-tag mb-2">Строительство</div>
                    <h3 class="h6 mb-2">
                        Контроль по объектам вместо «общего котла»
                    </h3>
                    <ul class="small text-muted mb-0">
                        <li>Выделили ДДС по каждому объекту</li>
                        <li>Настроили контроль авансов и этапов</li>
                        <li>Стали заранее видеть дефицит по объектам</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ЭКРАН 6: CTA-повтор (консультация) -->
<section id="cta-consult" class="page-section section-dark">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <h2 class="section-title mb-3">
                    Начнём с диагностической сессии
                </h2>
                <p class="section-subtitle mb-3">
                    За одну встречу мы поймём, где вы находитесь сейчас, покажем
                    ключевые точки потерь и предложим план на 3–6 месяцев.
                </p>
                <ul class="hero-list mb-3">
                    <li>Анализ текущих отчётов и платёжного календаря (если есть)</li>
                    <li>Оценка рисков по кассовым разрывам и обязательствам</li>
                    <li>Рекомендации по первому шагу уже в конце встречи</li>
                </ul>
                <p class="small text-light mb-0">
                    Формат: онлайн, 45–60 минут. Мы ничего не продаём «с ходу» —
                    сначала разбираемся в вашей ситуации.
                </p>
            </div>
            <div class="col-lg-5 offset-lg-1">
                <div class="card card-soft border-0">
                    <div class="card-body">
                        <h3 class="h6 mb-2 text-dark">Оставьте контакты</h3>
                        <p class="small text-muted mb-3">
                            Запишем вас на удобное время и уточним детали бизнеса.
                        </p>
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
                                <label class="form-label">Комментарий</label>
                                <textarea class="form-control" rows="2" placeholder="Коротко: в чём сейчас главная боль по финансам?"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                Записаться на консультацию
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ЭКРАН 7: Материалы / лид-магниты -->
<section id="materials" class="page-section section-muted">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-7">
                <h2 class="section-title">Материалы для собственника</h2>
                <p class="section-subtitle">
                    Если хотите сначала разобраться сами — используйте наши шаблоны и чек-листы.
                    Это те же инструменты, с которыми мы работаем на проектах.
                </p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card card-soft h-100 p-4">
                    <h3 class="h6 mb-2">Шаблон ДДС + платёжный календарь</h3>
                    <p class="small text-muted mb-3">
                        Готовая Google-таблица, где можно вести движение денег и видеть
                        кассовые разрывы заранее.
                    </p>
                    <ul class="small text-muted mb-3">
                        <li>Настроенные статьи ДДС для малого бизнеса</li>
                        <li>Автоматический расчёт остатка денег по дням</li>
                        <li>Инструкция по заполнению на 2 страницы</li>
                    </ul>
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        Получить шаблон
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-soft h-100 p-4">
                    <h3 class="h6 mb-2">Чек-лист: 15 ошибок в управлении деньгами</h3>
                    <p class="small text-muted mb-3">
                        Список типичных ошибок, из-за которых прибыль на бумаге есть,
                        а денег на счетах — нет.
                    </p>
                    <ul class="small text-muted mb-3">
                        <li>Ошибки в договорённостях с поставщиками и подрядчиками</li>
                        <li>Невидимые «пылесосы» денег в операционке</li>
                        <li>Что проверить в первую очередь за 1–2 дня</li>
                    </ul>
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        Скачать чек-лист
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ЭКРАН 8: Кратко про тарифы / форматы -->
<section class="page-section">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-7">
                <h2 class="section-title">Форматы работы</h2>
                <p class="section-subtitle">
                    Подбираем формат под масштаб, задачи и уровень зрелости вашего бизнеса.
                    Важнее не «тариф», а то, решаются ли ваши задачи.
                </p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-soft h-100 p-4">
                    <h3 class="h6 mb-1">Диагностика</h3>
                    <p class="small text-muted mb-2">
                        10–14 дней
                    </p>
                    <p class="small text-muted mb-3">
                        Быстрый аудит, карта потоков денег, рекомендации по первым шагам.
                    </p>
                    <p class="small text-muted mb-0">
                        Подходит, если вы только начинаете разбираться в финансах.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-soft h-100 p-4 border border-2 border-primary">
                    <h3 class="h6 mb-1">Финдиректор на аутсорсе</h3>
                    <p class="small text-muted mb-2">
                        От 3 месяцев
                    </p>
                    <p class="small text-muted mb-3">
                        Регулярное планирование, контроль и отчётность. ДДС, платёжный календарь,
                        анализ прибыли и рисков.
                    </p>
                    <p class="small text-muted mb-0">
                        Для бизнеса, который хочет расти и управлять прибылью, а не только выручкой.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-soft h-100 p-4">
                    <h3 class="h6 mb-1">Настройка фин-сервиса</h3>
                    <p class="small text-muted mb-2">
                        Сопровождение + облачный сервис
                    </p>
                    <p class="small text-muted mb-3">
                        Экономим ваше время: интеграции, автоправила, отчёты в пару кликов.
                    </p>
                    <p class="small text-muted mb-0">
                        Доступно клиентам, с которыми работаем на постоянной основе.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ЭКРАН 9: Блог / статьи -->
<section class="page-section section-muted">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-7">
                <h2 class="section-title">Статьи для предпринимателей</h2>
                <p class="section-subtitle">
                    Пишем о том, как управлять деньгами в малом и среднем бизнесе без
                    лишней «финансовой магии» и сложных терминов.
                </p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <h3 class="h6">ДДС, ОПиУ и Баланс «на пальцах»</h3>
                <p class="small text-muted">
                    Три отчёта, которые должен понимать собственник, чтобы управлять
                    деньгами и прибылью.
                </p>
                <a href="#" class="small">Читать статью →</a>
            </div>
            <div class="col-md-4">
                <h3 class="h6">Как перестать тушить кассовые разрывы</h3>
                <p class="small text-muted">
                    Пошаговый план, как внедрить платёжный календарь и вернуть контроль
                    над платежами.
                </p>
                <a href="#" class="small">Читать статью →</a>
            </div>
            <div class="col-md-4">
                <h3 class="h6">Когда бизнесу нужен финдиректор</h3>
                <p class="small text-muted">
                    Признаки того, что бухгалтер и «табличка в Excel» больше не справляются.
                </p>
                <a href="#" class="small">Читать статью →</a>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="page-section pt-4 pb-4">
    <div class="container footer">
        <div class="row align-items-center">
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="d-flex align-items-center">
                    <span class="brand-logo-badge" style="transform: scale(0.85); margin-right: 6px;">ВФ</span>
                    <span>Ваш ФинДиректор</span>
                </div>
                <div class="mt-2 small">
                    Финансовый директор на аутсорсе и сервис управления деньгами.
                </div>
            </div>
            <div class="col-md-6 text-md-end small">
                <div>Телеграм: @your_cfo_bot</div>
                <div>E-mail: hello@vashfindir.ru</div>
                <div class="mt-1">© <span id="year"></span> Ваш ФинДиректор</div>
            </div>
        </div>
    </div>
</footer>

</main>

<?php
get_footer();
?>

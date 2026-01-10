vashfindir тема-заглушка
========================

Установка:
- Скопируйте папку `vashfindir` в каталог `wp-content/themes/` вашего сайта WordPress.

Активация:
- В админке перейдите в "Внешний вид → Темы" и активируйте тему "vashfindir".

Описание:
- Минимальная заглушка без сторонних зависимостей. Отображает шапку, основной блок и футер даже при отсутствии контента.

Layout Rules (baseline)

### 1. Базовый принцип
Вертикальные отступы задаются ТОЛЬКО на уровне секций. Layout-контейнеры (html / body / main / container) НЕ управляют вертикальным ритмом.
Любой padding на layout-уровне = баг композиции.

### 2. Иерархия уровней (строго)
<body>
  <main>              ← НЕТ padding
    .container         ← НЕТ padding
      header/section    ← МОЖНО padding

### 3. Использование vf-section-pad-*
Разрешено:
- <section>
- Page header (<header>)
- Hero-блоки
- Контентные блоки

Запрещено:
- <main>
- .container
- любые layout-обёртки для выравнивания

### 4. Стандарты отступов
- vf-section-pad-lg — основные контентные секции
- vf-section-pad-md — page header / intro
- vf-section-pad-sm — вспомогательные блоки

Правило: никогда не использовать одновременно vf-section-pad-* на родителе и дочернем уровне.

### 5. Page Header (H1) — единственный паттерн
<div class="container">
  <header class="vf-section-pad-md">
    <p class="section-subtitle">...</p>
    <h1 class="page-title">...</h1>
  </header>
</div>

Запрещено:
<main class="vf-section-pad-lg">
<header class="vf-section-pad-lg">

### 6. Breadcrumbs
Breadcrumbs не управляют отступами страницы.
Никакого vf-section-pad-* на breadcrumbs.

<nav class="vf-breadcrumbs" aria-label="Breadcrumb"></nav>

### 7. Контентные секции
<div class="container">
  <section class="vf-section-pad-lg">
    ...
  </section>
</div>

Одна секция = один vertical spacing. Никаких дополнительных обёрток с padding.

### 8. Design System Sandbox
Sandbox использует только реальные классы темы.
Sandbox = эталон, не место для workaround’ов. Любой workaround в Sandbox запрещён в проде.

### 9. Checklist перед коммитом
- <main> без vf-section-pad-*
- .container без padding
- Отступы заданы только на section/header
- Нет вложенных vf-section-pad-*
- Sandbox визуально соответствует боевым страницам

### 10. Статус
Layout Rules — ARCHITECTURAL BASELINE.
Изменения только через новую версию (v+1). Отклонение = баг.

Коротко:
Layout управляется секциями, не контейнерами.

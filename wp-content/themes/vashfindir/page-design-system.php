<?php
/**
 * Template Name: Design System Sandbox
 *
 * Template for showcasing typography and UI components.
 *
 * @package vashfindir
 */

get_header();
get_template_part('template-parts/blocks/breadcrumbs');
?>

<main>
  <div class="container">
    <header class="vf-section-pad-md">
      <p class="section-subtitle">Design system</p>
      <h1 class="page-title">Typography & Components</h1>
    </header>
  </div>

  <div class="container">
    <section class="vf-section-pad-lg">
      <div class="row g-4">
        <div class="col-12 col-lg-7">
          <div class="vf-surface p-4 h-100">
            <h3>Шкала заголовков и текстов</h3>
            <p>Цель — минимализм, читаемость и “дорогая” уверенность. Без лишней декоративности.</p>
            <p>
              Основной шрифт (body):
              <span class="badge rounded-pill bg-light text-primary border">system-ui, -apple-system, &quot;Segoe UI&quot;, Roboto, Arial, sans-serif</span>
              <br>
              Токен:
              <span class="badge rounded-pill bg-light text-primary border">--font-sans</span>
              →
              <span class="badge rounded-pill bg-light text-primary border">body { font-family: var(--font-sans); }</span>
            </p>
            <div class="mt-4">
              <p class="mb-1"><strong>H1 — Заголовок</strong></p>
              <p>Размер: <span class="badge rounded-pill bg-light text-primary border">clamp(32px, 3.2vw, 46px)</span>, вес: <span class="badge rounded-pill bg-light text-primary border">900</span></p>
              <p class="mb-1"><strong>H2 — Подзаголовок</strong></p>
              <p>Размер: <span class="badge rounded-pill bg-light text-primary border">clamp(24px, 2.1vw, 34px)</span>, вес: <span class="badge rounded-pill bg-light text-primary border">900</span></p>
              <p class="mb-1"><strong>H3 — Заголовок карточки</strong></p>
              <p>Размер: <span class="badge rounded-pill bg-light text-primary border">20px</span>, вес: <span class="badge rounded-pill bg-light text-primary border">900</span></p>
              <p class="mb-1"><strong>Body — обычный текст</strong></p>
              <p>Размер: <span class="badge rounded-pill bg-light text-primary border">16px</span>, line-height: <span class="badge rounded-pill bg-light text-primary border">1.55</span></p>
              <p class="mb-1"><strong>Small / helper</strong></p>
              <p>Размер: <span class="badge rounded-pill bg-light text-primary border">13px</span>, line-height: <span class="badge rounded-pill bg-light text-primary border">1.45</span></p>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-5">
          <div class="vf-surface p-4 h-100">
            <h3>Лучшие практики (коротко)</h3>
            <ul class="mb-0">
              <li>1 смысл = 1 акцент (CTA один цвет).</li>
              <li>Текст ограничиваем до <span class="badge rounded-pill bg-light text-primary border">56–68ch</span>.</li>
              <li>Отступы секций: <span class="badge rounded-pill bg-light text-primary border">clamp(48px..80px)</span>.</li>
              <li>Весов немного: <span class="badge rounded-pill bg-light text-primary border">400 / 600 / 900</span>.</li>
              <li>Карточки — единый padding: <span class="badge rounded-pill bg-light text-primary border">16–20px</span>.</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section class="vf-section-pad-lg">
      <p class="section-subtitle">Headings</p>
      <h2 class="section-title">Section title example</h2>
      <h3>Another section title</h3>
      <p>Paragraph text example to showcase body copy styles. Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
    </section>

    <section class="vf-section-pad-lg">
      <p class="section-subtitle">Card surface</p>
      <div class="vf-surface p-4">
        <h3>vf-surface card</h3>
        <p>This surface demonstrates spacing, background, and typography within a card-like component.</p>
      </div>
    </section>

    <section class="vf-section-pad-lg">
      <p class="section-subtitle">Accordion / FAQ</p>
      <div class="vf-faq__body">
        <details class="vf-faq__item vf-surface">
          <summary class="vf-faq__question">What is this page for?</summary>
          <div class="vf-faq__answer">
            <p>It showcases the existing typography and UI components in the theme.</p>
          </div>
        </details>
        <details class="vf-faq__item vf-surface">
          <summary class="vf-faq__question">How do I use it?</summary>
          <div class="vf-faq__answer">
            <p>Create a page in WordPress and select the “Design System Sandbox” template.</p>
          </div>
        </details>
      </div>
    </section>

    <section class="vf-section-pad-lg">
      <p class="section-subtitle">Tariffs</p>
      <h2 class="section-title">Tariffs-2</h2>
    </section>

  </div>

  <?php get_template_part('template-parts/blocks/tariffs-2'); ?>
</main>

<?php
get_footer();

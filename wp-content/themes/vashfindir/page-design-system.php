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
      <p class="section-subtitle">Headings</p>
      <h2 class="section-title">Section title example</h2>
      <h3>Another section title</h3>
      <p>Paragraph text example to showcase body copy styles. Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
    </section>

    <section class="vf-section-pad-lg">
      <div class="row g-4">
        <div class="col-12 col-lg-7">
          <div class="card-e p-4">
            <h2>Шкала заголовков и текстов</h2>
            <div class="prose">
              <p class="lead">Цель — минимализм, читаемость и “дорогая” уверенность. Без лишней декоративности.</p>
              <div class="mt-3 small">
                Основной шрифт (body): <code>system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif</code><br>
                Токен: <code>--font-sans</code> → <code>body { font-family: var(--font-sans); }</code>
              </div>
              <div class="mt-4">
                <h1>H1 — Заголовок</h1>
                <div class="footer-muted small">Размер: <code>clamp(32px, 3.2vw, 46px)</code>, вес: <code>900</code></div>
              </div>
              <div class="mt-4">
                <h2>H2 — Подзаголовок</h2>
                <div class="footer-muted small">Размер: <code>clamp(24px, 2.1vw, 34px)</code>, вес: <code>900</code></div>
              </div>
              <div class="mt-4">
                <h3>H3 — Заголовок карточки</h3>
                <div class="footer-muted small">Размер: <code>20px</code>, вес: <code>900</code></div>
              </div>
              <div class="mt-4">
                <div style="color:var(--brand);">Body — обычный текст</div>
                <div class="footer-muted small">Размер: <code>16px</code>, line-height: <code>1.55</code></div>
              </div>
              <div class="mt-4">
                <div style="color:var(--brand);">Small / helper</div>
                <div class="footer-muted small">Размер: <code>13px</code>, line-height: <code>1.45</code></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-5">
          <div class="card-e p-4 h-100">
            <h2>Лучшие практики (коротко)</h2>
            <ul class="mb-0" style="padding-left: 1.1rem;">
              <li class="mb-2"><span>1 смысл = 1 акцент</span> (CTA один цвет).</li>
              <li class="mb-2">Текст ограничиваем до <code>56–68ch</code>.</li>
              <li class="mb-2">Отступы секций: <code>clamp(48px..80px)</code>.</li>
              <li class="mb-2">Весов немного: <code>400 / 600 / 900</code>.</li>
              <li>Карточки — единый padding: <code>16–20px</code>.</li>
            </ul>
          </div>
        </div>
      </div>
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

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
    <section class="vf-section-pad-md vf-buttons-showcase">
      <div class="vf-surface vf-buttons-showcase__card p-4 p-lg-5">
        <div class="row g-4 align-items-start">
          <div class="col-12 col-lg-7">
            <h2 class="vf-buttons-showcase__title">Кнопки: CTA и статусы</h2>
            <p class="vf-buttons-showcase__lead">
              Минимализм + доверие: один “основной” CTA, спокойные вторичные
              действия, красный — только риск.
            </p>
            <p class="vf-buttons-showcase__note">
              Цветовая логика для Enterprise B2B (финансы):
              <span class="vf-buttons-showcase__chip">Blue = действие</span>
              <span class="vf-buttons-showcase__chip">Navy = структура</span>
              <span class="vf-buttons-showcase__chip">Red = риск</span>
            </p>
          </div>
          <div class="col-12 col-lg-5">
            <div class="vf-buttons-showcase__examples">
              <div class="vf-buttons-showcase__label">Примеры</div>
              <div class="vf-buttons-showcase__grid">
                <button class="vf-btn vf-buttons-showcase__btn vf-buttons-showcase__btn--cta" type="button">
                  CTA (заливка, синий)
                </button>
                <button class="vf-btn vf-buttons-showcase__btn vf-buttons-showcase__btn--cta-danger" type="button">
                  CTA (референс, красный)
                </button>
                <button class="vf-btn vf-buttons-showcase__btn vf-buttons-showcase__btn--outline" type="button">
                  Secondary (outline)
                </button>
                <button class="vf-btn vf-buttons-showcase__btn vf-buttons-showcase__btn--brand-outline" type="button">
                  Primary (brand outline)
                </button>
                <button class="vf-btn vf-buttons-showcase__btn vf-buttons-showcase__btn--ghost" type="button">
                  Ghost / нейтральная
                </button>
                <div class="vf-buttons-showcase__status-row">
                  <span class="vf-buttons-showcase__badge vf-buttons-showcase__badge--success">Success</span>
                  <span class="vf-buttons-showcase__badge vf-buttons-showcase__badge--warning">Warning</span>
                  <span class="vf-buttons-showcase__badge vf-buttons-showcase__badge--danger">Danger</span>
                </div>
                <div class="vf-buttons-showcase__chip-row">
                  <span class="vf-buttons-showcase__pill">
                    CTA fill: <code>--cta-secondary</code>
                  </span>
                  <span class="vf-buttons-showcase__pill">
                    CTA ref: <code>--cta</code>
                  </span>
                  <span class="vf-buttons-showcase__pill">
                    Brand: <code>--brand</code>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="vf-buttons-showcase__divider"></div>
        <div class="row g-3">
          <div class="col-12 col-lg-4">
            <div class="vf-buttons-showcase__info">
              <div class="vf-buttons-showcase__info-title">PRIMARY CTA</div>
              <p>Регистрация / Оплатить / Начать — синий fill (в финтехе воспринимается как “контроль”).</p>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="vf-buttons-showcase__info">
              <div class="vf-buttons-showcase__info-title">SECONDARY</div>
              <p>Войти / Подробнее / Сравнить — outline, без лишнего давления.</p>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="vf-buttons-showcase__info">
              <div class="vf-buttons-showcase__info-title">DANGER</div>
              <p>Удалить / критические риски — только красный. Не использовать как маркетинговый акцент.</p>
            </div>
          </div>
        </div>
      </div>
    </section>
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

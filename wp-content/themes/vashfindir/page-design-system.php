<?php
/**
 * Template Name: Design System Sandbox
 *
 * Template for showcasing typography and UI components.
 *
 * @package vashfindir
 */

get_header();
?>

<?php get_template_part('template-parts/blocks/breadcrumbs'); ?>

<main class="vf-section-pad-lg">
  <div class="container">
    <header class="vf-section-pad-lg">
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
      <p class="section-subtitle">Breadcrumbs</p>
      <h2 class="section-title">Breadcrumbs</h2>
    </section>
  </div>
</main>

<?php
get_footer();

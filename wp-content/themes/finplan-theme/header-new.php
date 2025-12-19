<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
  <!-- NAV -->
  <nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="#top" aria-label="Ваш Финдир">
        <span class="brand-badge"><i class="bi bi-graph-up-arrow"></i></span>
        <span class="fw-bold" style="color:var(--brand); letter-spacing:-0.02em;">Ваш Финдир</span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
              aria-controls="nav" aria-expanded="false" aria-label="Открыть меню">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
          <?php
          wp_nav_menu([
            'theme_location' => 'primary',
            'container' => false,
            'items_wrap' => '%3$s',
            'fallback_cb' => false,
          ]);
          ?>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-outline-brand px-3" href="#login">Войти</a>
          </li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-brand px-3" href="#register">Регистрация</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>






  <!-- HERO -->
  <header class="hero" id="top">
    <div class="container">
      <div class="row align-items-center g-4">
        <!-- TEXT -->
        <div class="col-lg-6 order-2 order-lg-1">
          <h1 class="mb-3">
            Управляйте деньгами и прибылью —
            <span class="d-inline d-sm-none"><br></span>
            растите уверенно
          </h1>

          <p class="lead lead-muted mb-4">Сервис + финдиректор: дашборд, прогноз и платёжные решения для управляемого роста.</p>

          <div class="d-flex flex-column flex-sm-row gap-2">
            <button type="button" class="btn btn-accent btn-lg px-4">
              Начать с сервиса
            </button>
            <button type="button" class="btn btn-outline-brand btn-lg px-4">
              Сервис + финдиректор
            </button>
          </div>
        </div>

        <!-- VISUAL -->
        <div class="col-lg-6 order-1 order-lg-2">
          <div class="hero-media">
            <picture>
              <source media="(max-width: 575.98px)" srcset="hero-1-mobile.png">
              <source media="(max-width: 991.98px)" srcset="hero-1-tablet.png">
              <img src="hero-1-desktop.png" alt="Ваш Финдир — сервис и финдиректор" loading="eager" decoding="async">
            </picture>
          </div>
        </div>
      </div>
    </div>
  </header>

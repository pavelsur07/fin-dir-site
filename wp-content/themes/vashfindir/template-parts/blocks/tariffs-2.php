<?php

if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="section" id="tariffs-2">
<div class="container">
<div class="vf-pr2-head">
<h2 class="mb-0">Тарифы</h2>
<p class="vf-pr2-sub">Выберите роль и период оплаты — мы покажем нужные смыслы, а цены пересчитаются автоматически.</p>
<div aria-label="Переключатели тарифов" class="vf-pr2-controls">
<div aria-label="Аудитория" class="vf-seg" role="group">
<button aria-pressed="true" class="vf-seg-btn" data-audience="owner" type="button">Я владелец бизнеса</button>
<button aria-pressed="false" class="vf-seg-btn" data-audience="cfo" type="button">Я CFO / партнёр</button>
</div>
<div aria-label="Период оплаты" class="vf-seg" role="group">
<button aria-pressed="true" class="vf-seg-btn" data-term="1" type="button">1 мес.</button>
<button aria-pressed="false" class="vf-seg-btn" data-term="3" type="button">3 мес.</button>
<button aria-pressed="false" class="vf-seg-btn" data-term="6" type="button">6 мес.</button>
<button aria-pressed="false" class="vf-seg-btn" data-term="12" type="button">12 мес.</button>
</div>
</div>
</div>
<div aria-label="Карточки тарифов" class="vf-pr2-grid" id="vf-pr2-cards"></div>
<div class="vf-pr2-cfo-extra" data-open="false" hidden="" id="vf-pr2-cfo-extra">
<div class="vf-surface vf-pr2-cfo-box">
<div class="vf-pr2-cfo-box__head">
<h3 class="mb-0">Партнёрская модель</h3>
<p class="mb-0 vf-pr2-cfo-note">Для CFO и партнёров: подключайте клиентов и получайте % от подписки.</p>
</div>
<div class="vf-pr2-cfo-box__grid">
<div class="vf-pr2-cfo-kpi">
<div class="vf-pr2-cfo-kpi__label">Вознаграждение</div>
<div class="vf-pr2-cfo-kpi__value">20–30%</div>
</div>
<div class="vf-pr2-cfo-kpi">
<div class="vf-pr2-cfo-kpi__label">Выплаты</div>
<div class="vf-pr2-cfo-kpi__value">ежемесячно</div>
</div>
<div class="vf-pr2-cfo-kpi">
<div class="vf-pr2-cfo-kpi__label">Материалы</div>
<div class="vf-pr2-cfo-kpi__value">лендинги + кабинет</div>
</div>
</div>
<div class="vf-pr2-cfo-actions">
<a class="btn btn-primary" href="#lead2">Стать партнёром</a>
<button class="btn btn-outline-primary" id="vf-pr2-cfo-close" type="button">Скрыть</button>
</div>
</div>
</div>
</div>

<script>
  (function(){
    const root = document.getElementById('tariffs-2');
    if(!root) return;

    const cardsEl = root.querySelector('#vf-pr2-cards');
    const cfoExtra = root.querySelector('#vf-pr2-cfo-extra');
    const btnCfoClose = root.querySelector('#vf-pr2-cfo-close');

    const state = {
      audience: 'owner',
      term: 1
    };

    const fmt = (n)=> new Intl.NumberFormat('ru-RU').format(n);

    const data = {
      owner: {
        base: [
          {
            key: 'start',
            badge: 'MVP',
            title: 'Start',
            desc: 'Для владельца, которому нужно навести порядок и видеть реальную картину.',
            priceMonth: 4900,
            features: [
              'ДДС: факт + план',
              'Платёжный календарь',
              'ОПиУ (P&L) базовый',
              'Отчёты по периодам',
              '1 компания / 1 пользователь'
            ],
            ctaText: 'Попробовать Start',
            ctaHref: '#lead2'
          },
          {
            key: 'pro',
            badge: 'Рекомендуем',
            title: 'Pro',
            desc: 'Для бизнеса, который растёт и хочет контроль без ручного хаоса.',
            priceMonth: 9900,
            features: [
              'Всё из Start',
              'Команда (права доступа)',
              'Кредиты и графики',
              'Сценарное планирование',
              'Интеграции (позже: WB/Ozon/1C)'
            ],
            ctaText: 'Выбрать Pro',
            ctaHref: '#lead2',
            highlight: true
          },
          {
            key: 'max',
            badge: 'Сервис',
            title: 'Max + CFO',
            desc: 'Под ключ: сервис + финансовый директор, который внедряет и ведёт.',
            priceMonth: 49900,
            features: [
              'Всё из Pro',
              'Внедрение + настройка',
              'Регламенты и отчётность',
              'Еженедельные созвоны',
              'Контроль кассовых разрывов'
            ],
            ctaText: 'Обсудить Max',
            ctaHref: '#lead2'
          }
        ],
        discounts: {
          1: 1.00,
          3: 0.93,
          6: 0.88,
          12: 0.80
        }
      },
      cfo: {
        base: [
          {
            key: 'partner',
            badge: 'Партнёр',
            title: 'Partner',
            desc: 'Для CFO: подключайте клиентов и получайте вознаграждение.',
            priceMonth: 0,
            features: [
              'Партнёрский кабинет (v1)',
              'Материалы для продаж',
              'Ссылка/лендинг на ваш бренд',
              'Выплаты 20–30% от подписки'
            ],
            ctaText: 'Стать партнёром',
            ctaHref: '#lead2'
          },
          {
            key: 'agency',
            badge: 'Агентство',
            title: 'Agency',
            desc: 'Для агентств: white-label и масштабирование через команду.',
            priceMonth: 0,
            features: [
              'White-label (v1)',
              'Мульти-клиентский доступ',
              'Роли и права',
              'Приоритетная поддержка'
            ],
            ctaText: 'Запросить условия',
            ctaHref: '#lead2',
            highlight: true
          },
          {
            key: 'enterprise',
            badge: 'B2B',
            title: 'Enterprise',
            desc: 'Для интеграторов и крупных клиентов: кастом и SLA.',
            priceMonth: 0,
            features: [
              'SLA / сопровождение',
              'Кастомные интеграции',
              'Импорт/миграция данных',
              'Обучение команды'
            ],
            ctaText: 'Запросить демо',
            ctaHref: '#lead2'
          }
        ],
        discounts: {
          1: 1.00,
          3: 1.00,
          6: 1.00,
          12: 1.00
        }
      }
    };

    function setPressed(groupEl, btn){
      groupEl.querySelectorAll('button[aria-pressed]').forEach(b=> b.setAttribute('aria-pressed','false'));
      btn.setAttribute('aria-pressed','true');
    }

    root.querySelectorAll('.vf-seg').forEach(seg=>{
      seg.addEventListener('click',(e)=>{
        const btn = e.target.closest('button[data-audience],button[data-term]');
        if(!btn) return;

        if(btn.dataset.audience){
          state.audience = btn.dataset.audience;
          setPressed(seg, btn);
          if(state.audience === 'cfo'){
            cfoExtra.hidden = false;
            cfoExtra.dataset.open = "true";
          }else{
            cfoExtra.hidden = true;
            cfoExtra.dataset.open = "false";
          }
        }

        if(btn.dataset.term){
          state.term = parseInt(btn.dataset.term,10);
          setPressed(seg, btn);
        }

        render();
      });
    });

    if(btnCfoClose){
      btnCfoClose.addEventListener('click', ()=>{
        cfoExtra.hidden = true;
        cfoExtra.dataset.open = "false";
      });
    }

    function buildCard(item){
      const disc = data[state.audience].discounts[state.term] || 1;
      const price = Math.round(item.priceMonth * state.term * disc);

      const priceHtml = item.priceMonth > 0
        ? `<div class="vf-price">
            <div class="vf-price__value">${fmt(price)} ₽</div>
            <div class="vf-price__meta">за ${state.term} мес.</div>
          </div>`
        : `<div class="vf-price">
            <div class="vf-price__value">По запросу</div>
            <div class="vf-price__meta">условия обсуждаются</div>
          </div>`;

      const badgeHtml = item.badge ? `<div class="vf-pr2-badge">${item.badge}</div>` : '';

      const li = item.features.map(f=> `<li><i class="bi bi-check-circle-fill"></i><span>${f}</span></li>`).join('');

      const cls = item.highlight ? 'vf-pr2-card vf-pr2-card--hot' : 'vf-pr2-card';

      return `<div class="${cls}">
        <div class="vf-pr2-card__head">
          ${badgeHtml}
          <h3 class="vf-pr2-title">${item.title}</h3>
          <p class="vf-pr2-desc">${item.desc}</p>
        </div>
        ${priceHtml}
        <ul class="vf-pr2-list">${li}</ul>
        <div class="vf-pr2-actions">
          <a class="btn btn-primary w-100" href="${item.ctaHref}" data-plan="${item.key}">${item.ctaText}</a>
        </div>
      </div>`;
    }

    function render(){
      cardsEl.innerHTML = data[state.audience].base.map(buildCard).join('');

      // метрики кликов
      cardsEl.querySelectorAll('a[data-plan]').forEach(a=>{
        a.addEventListener('click', ()=>{
          const sp = new URLSearchParams(window.location.search);
          const plan = a.getAttribute('data-plan');
          // no-op: hook for analytics
          if(window.ym){
            ym(0, 'reachGoal', 'pricing_cta_click', { plan, audience: state.audience, term: state.term });
          }
          if(window.gtag){
            gtag('event','pricing_cta_click',{ plan, audience: state.audience, term: state.term });
          }
          if(window.dataLayer){
            window.dataLayer.push({event:'pricing_cta_click', plan, audience: state.audience, term: state.term});
          }
          // keep optional: UTM passthrough
          if(sp.get('utm_source')){
            // no action (kept 1:1)
          }
        }, { once: true });
      });
    }

    render();
  })();
  </script>
</section>

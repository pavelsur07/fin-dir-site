<?php

if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="section pt-5 pb-5" id="tariffs-2">
    <div class="container">
        <div class="vf-pr2-head">
            <h2 class="section-title vf-control__title">Тарифы</h2>
            <p class="section-subtitle vf-faq__subtitle">Выберите роль и период оплаты — мы покажем нужные смыслы, а цены пересчитаются автоматически.</p>
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
            <div aria-expanded="false" class="vf-pr2-cfo-extra__head" role="button" tabindex="0">
            <h3 class="vf-pr2-cfo-extra__title">Показать все фичи (для CFO)</h3>
            <div aria-hidden="true" class="vf-badge-save">Сравнение</div>
            </div>
            <div class="vf-pr2-cfo-extra__body">
            <table aria-label="Сравнение функций" class="vf-pr2-cfo-extra__table">
            <thead>
            <tr>
            <th style="width:48%;">Функция</th>
            <th>Контроль денег</th>
            <th>Управление прибылью</th>
            <th>Финансовая система</th>
            </tr>
            </thead>
            <tbody>
            <tr>
            <td>Банки + авторазнесение</td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            </tr>
            <tr>
            <td>Платежный календарь + заявки</td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            </tr>
            <tr>
            <td>PnL / рентабельность</td>
            <td class="vf-no">—</td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            </tr>
            <tr>
            <td>Баланс / обязательства</td>
            <td class="vf-no">—</td>
            <td class="vf-no">—</td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            </tr>
            <tr>
            <td>Интеграции 1С / МойСклад (по мере готовности)</td>
            <td class="vf-no">—</td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            </tr>
            <tr>
            <td>AI-инсайты / сценарии (по мере готовности)</td>
            <td class="vf-no">—</td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            </tr>
            <tr>
            <td>Telegram-управление</td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            <td class="vf-ok"><i class="bi bi-check-circle-fill"></i></td>
            </tr>
            </tbody>
            </table>
            </div>
            </div>
            <p class="text-muted mt-3 mb-0" style="font-size:12px;">
                  Цены в демо пересчитываются на лету. Скидки/коэффициенты задаются конфигом, без правок верстки.
                </p>
    </div>
    <script>
      (function(){
        const cfg = {
          discounts: { 1: 1.00, 3: 0.95, 6: 0.90, 12: 0.85 },
          plans: [
            {
              id: "starter",
              title: "Контроль денег",
              priceMonthly: 3990,
              subtitles: {
                owner: "Вы всегда знаете, сколько денег у бизнеса и что будет дальше.",
                cfo: "База для ежедневного контроля платежей и кассы клиента."
              },
              bullets: {
                owner: [
                  "Деньги под контролем каждый день",
                  "План платежей вперёд",
                  "Отчёты и бот без входа в систему",
                  "Консультации и методология"
                ],
                cfo: [
                  "Банки + авторазнесение",
                  "Платёжный календарь + заявки",
                  "ДДС (кассовый метод)",
                  "Telegram-управление"
                ]
              }
            },
            {
              id: "base",
              title: "Управление прибылью",
              priceMonthly: 13750,
              subtitles: {
                owner: "Вы понимаете, где бизнес зарабатывает, а где теряет деньги.",
                cfo: "PnL и рентабельность: ведение клиента как финансового проекта."
              },
              bullets: {
                owner: [
                  "Маржа и прибыль по продуктам/направлениям",
                  "План‑факт по расходам",
                  "Понятные отчёты для решений",
                  "Консультации и методология"
                ],
                cfo: [
                  "Банки + авторазнесение",
                  "Платёжный календарь + заявки",
                  "ДДС + PnL",
                  "Интеграции 1С/МойСклад (по мере готовности)",
                  "AI‑инсайты (по мере готовности)",
                  "Telegram-управление"
                ]
              }
            },
            {
              id: "pro",
              title: "Финансовая система",
              priceMonthly: 23900,
              subtitles: {
                owner: "Бизнес управляется как система, а не через тушение пожаров.",
                cfo: "Баланс, обязательства, сверки и сценарии — уровень CFO‑практики."
              },
              bullets: {
                owner: [
                  "Сценарии и решения до кассовых разрывов",
                  "Управление долгом и обязательствами",
                  "Финансовая картина бизнеса целиком",
                  "Регулярный ритм управления"
                ],
                cfo: [
                  "Банки + авторазнесение",
                  "Платёжный календарь + заявки",
                  "ДДС + PnL + баланс",
                  "Консолидация (по мере готовности)",
                  "AI‑сценарии (по мере готовности)",
                  "Telegram-управление"
                ]
              },
              featured: true
            }
          ]
        };

        const state = { audience: "owner", term: 1 };

        const fmt = (n) => new Intl.NumberFormat("ru-RU").format(n) + " ₽";
        const round = (n) => Math.round(n);

        const cardsWrap = document.getElementById("vf-pr2-cards");
        const cfoExtra = document.getElementById("vf-pr2-cfo-extra");
        const cfoHead = cfoExtra ? cfoExtra.querySelector(".vf-pr2-cfo-extra__head") : null;

        function compute(plan){
          const k = cfg.discounts[state.term] ?? 1;
          const total = round(plan.priceMonthly * state.term * k);
          const per = round(total / state.term);
          const full = plan.priceMonthly * state.term;
          const savings = Math.max(0, round(full - total));
          const pct = savings > 0 ? Math.round((savings / full) * 100) : 0;
          return { total, per, savings, pct };
        }

        function link(planId){
          const params = new URLSearchParams({
            tariff: planId,
            term: String(state.term),
            audience: state.audience
          });
          return "#checkout?" + params.toString();
        }

        function render(){
          cardsWrap.innerHTML = "";
          cfg.plans.forEach((p, idx) => {
            const c = compute(p);
            const el = document.createElement("div");
            el.className = "vf-plan" + (p.featured ? " is-featured" : "");
            el.innerHTML = `
              <div class="vf-plan__inner">
                <div class="vf-plan__top">
                  <h3 class="vf-plan__title">${p.title}</h3>
                  <p class="vf-plan__subtitle">${p.subtitles[state.audience]}</p>
                </div>

                <div class="vf-price">
                  <div class="vf-price__main">
                    <div class="vf-price__per">${fmt(c.per).replace(" ₽","")}</div>
                    <div class="vf-price__unit">₽/мес</div>
                    ${c.savings > 0 ? `<span class="vf-badge-save">-${c.pct}%</span>` : ``}
                  </div>
                  <p class="vf-price__meta">Итого за период: <b>${fmt(c.total)}</b>${c.savings > 0 ? ` · экономия <b>${fmt(c.savings)}</b>` : ``}</p>
                </div>

                <ul class="list-unstyled feature-list mb-4">
                  ${(p.bullets[state.audience] || []).map(x => `<li><i class="bi bi-check-circle-fill"></i> ${x}</li>`).join("")}
                </ul>

                <div class="vf-plan__cta">
                  <a class="${idx === 0 || idx === 2 ? "vf-cta-btn-outline" : "vf-cta-btn-secondary"}" href="${link(p.id)}">Подключить</a>
                  <a class="vf-plan__link" href="${state.audience === "owner" ? "#demo" : "#compare"}">
                    ${state.audience === "owner" ? "Посмотреть, как работает" : "Скачать список фич / открыть сравнение"}
                  </a>
                </div>
              </div>
            `;
            cardsWrap.appendChild(el);
          });

          if (!cfoExtra) return;
          if (state.audience === "cfo"){
            cfoExtra.hidden = false;
          } else {
            cfoExtra.hidden = true;
            cfoExtra.dataset.open = "false";
            if (cfoHead) cfoHead.setAttribute("aria-expanded", "false");
          }
        }

        // Segmented controls
        document.querySelectorAll('#tariffs-2 [data-audience]').forEach(btn => {
          btn.addEventListener("click", () => {
            state.audience = btn.dataset.audience;
            document.querySelectorAll('#tariffs-2 [data-audience]').forEach(b => b.setAttribute("aria-pressed", String(b === btn)));
            if (window.ym) ym(105455340, 'reachGoal', 'pricing_audience_switch', {audience: state.audience});
            render();
          });
        });

        document.querySelectorAll('#tariffs-2 [data-term]').forEach(btn => {
          btn.addEventListener("click", () => {
            state.term = parseInt(btn.dataset.term, 10);
            document.querySelectorAll('#tariffs-2 [data-term]').forEach(b => b.setAttribute("aria-pressed", String(b === btn)));
            if (window.ym) ym(105455340, 'reachGoal', 'pricing_term_switch', {term: state.term});
            render();
          });
        });

        // CFO extra toggle
        if (cfoHead){
          const toggle = () => {
            const open = cfoExtra.dataset.open === "true";
            cfoExtra.dataset.open = open ? "false" : "true";
            cfoHead.setAttribute("aria-expanded", open ? "false" : "true");
          };
          cfoHead.addEventListener("click", toggle);
          cfoHead.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.key === " "){ e.preventDefault(); toggle(); }
          });
        }

        // CTA click analytics (delegate)
        document.addEventListener("click", (e) => {
          const a = e.target.closest('#tariffs-2 a.vf-cta-btn-secondary, #tariffs-2 a.vf-cta-btn-outline');
          if (!a) return;
          const url = new URL(a.getAttribute("href"), window.location.href);
          const sp = url.searchParams;
          if (window.ym) ym(105455340, 'reachGoal', 'pricing_cta_click', {
            plan: sp.get('tariff'),
            term: sp.get('term'),
            audience: sp.get('audience')
          });
        });

        render();
      })();
      </script>
</section>

// ═══ Постоянный дашборд Conwix (правый рельс, следует за разделом) ═══
// Компоненты: бейдж раздела + шкала «цикл недели» + спарклайн + 4 кликабельных показателя.
window.DASH = (() => {
  const canvas = document.getElementById("dash-canvas");
  if (!canvas) return { set: () => {}, ERAS: {} };
  const { fit } = U.bindCanvas(canvas);
  const ctx = canvas.getContext("2d");
  let view = fit();
  window.addEventListener("resize", () => { view = fit(); draw(); });

  const PHASES = ["Диагностика", "Лимиты", "План", "Контроль"];
  const M = { l: 30, r: 30, t: 42 };

  // ── Состояния разделов: цифры из RPT, источники датированы ──
  const ERAS = {
    frame: { badge: "§1 · Рынок", title: "Рост рынка ≠ рост маржи", pi: 0,
      curve: { data: RPT.market.gmv, cats: RPT.market.years, fmt: v => v.toFixed(1) + " трлн ₽", label: "Интернет-торговля РФ" },
      stats: [
        { v: "11,5 трлн ₽", k: "рынок 2025, +28%", drill: RPT.market.drills[2025] },
        { v: "18,8%", k: "доля онлайна в рознице", drill: { title: "Доля онлайна", value: "18,8% розницы", sub: "2023: 13,5% → 2024: 16,2% → 2025: 18,8% (+2,6 п.п. за год).", source: "АКИТ, 2026-02" } },
        { v: "81%", k: "заказов у топ-3 МП", drill: { title: "Концентрация рынка", value: "81% заказов · 62% объёма", sub: "Wildberries, Ozon и «Яндекс Маркет»; совокупные продажи 8,59 трлн ₽ (+32,2%).", source: "Data Insight · Infoline, 2026-03" } },
        { v: "−2%", k: "селлеров стало меньше", drill: RPT.sellers.drills[2025] },
      ] },
    leaks: { badge: "§2 · Юнит", title: "Шесть вычетов из цены", pi: 0,
      curve: { data: [1990, 1612, 1470, 1452, 1213, 1153, 453], cats: RPT.waterfall.steps.map(s => s.k), fmt: v => Math.round(v) + " ₽", label: "Остаток цены после вычетов" },
      stats: [
        { v: "50–70%", k: "выручки съедают издержки", drill: RPT.now[0].d },
        { v: "453 ₽", k: "прибыль демо-SKU", drill: RPT.waterfall.steps[7].d },
        { v: "12%", k: "ДРР в примере", drill: RPT.waterfall.steps[4].d },
        { v: "25–40%", k: "комиссии площадок", drill: { title: "Комиссии маркетплейсов", value: "25–40% выручки", sub: "Рост комиссий за три года — 58–63%; логистики — 33–89%.", source: "«МойСклад»/«Точка», 2026-02" } },
      ] },
    growth: { badge: "§3 · Рост", title: "Карта «эффект × усилия»", pi: 1,
      curve: { data: RPT.growth.points.map(p => p.y), cats: RPT.growth.points.map(p => p.label), fmt: v => "+" + v.toFixed(1) + " п.п.", label: "Эффект на маржу, п.п." },
      stats: [
        { v: "6", k: "точек роста на карте", drill: { title: "Шесть точек роста", value: "ДРР · распродажа · бюджет · вывод SKU · Kсезон · возвраты", sub: "Ранжирование по эффекту на маржу к усилиям внедрения.", source: "Методика Conwix" } },
        { v: "+3–5 п.п.", k: "лимиты ДРР", drill: RPT.growth.points[0].d },
        { v: "1 нед", k: "до первого эффекта", drill: { title: "Скорость результата", value: "Первая неделя", sub: "Лимиты ДРР и план распродажи перекрывают текущую утечку денег, а не инвестируют в будущее.", source: "Методика Conwix" } },
        { v: "5–6 п.п.", k: "годовое сжатие маржи", drill: RPT.now[2].d },
      ] },
    drr: { badge: "§4 · ДРР", title: "Факт vs лимит vs безубыток", pi: 1,
      curve: { data: RPT.drr.skus.map(s => s.fact), cats: RPT.drr.skus.map(s => s.name), fmt: v => v.toFixed(1) + "%", label: "ДРР факт по SKU" },
      stats: [
        { v: "5 / 2 / 1", k: "ОК / ВНИМАНИЕ / СТОП", drill: { title: "Статусы портфеля", value: "5 ОК · 2 ВНИМАНИЕ · 1 СТОП", sub: "СТОП: «Рюкзак городской» — факт 23,5% при безубытке 20,6%. ВНИМАНИЕ: «Коврик для йоги», «Плед флисовый».", source: "Демо-данные Conwix" } },
        { v: "70/15/15", k: "сплит бюджета", drill: { title: "Структура рекламного бюджета", value: "Ядро ~70% · тесты ~15% · распродажа ~15%", sub: "Корзины не перетекают друг в друга без пересчёта лимитов.", source: "Методика Conwix" } },
        { v: "1 день", k: "скорость рычага", drill: { title: "Реклама — самый быстрый рычаг", value: "Эффект за 1 день", sub: "Единственная статья расходов, которую селлер меняет немедленно.", source: "Методика Conwix" } },
        { v: "15%", k: "целевая маржа в демо", drill: { title: "Параметр: целевая маржа", value: "15%", sub: "Лимит ДРР выводится из целевой маржи; параметр редактируется, лимиты пересчитываются.", source: "Демо-данные Conwix" } },
      ] },
    season: { badge: "§5 · Сезон", title: "План = Base × Kсезон × Kцена × Kрынок", pi: 2,
      curve: { data: RPT.season.weeks.map(p => p.v), cats: RPT.season.weeks.map(p => "н" + p.w), fmt: v => "K=" + v.toFixed(2), label: "Kсезон ниши, 52 недели" },
      stats: [
        { v: "×1,55", k: "пик декабря", drill: RPT.season.drill(RPT.season.weeks[14]) },
        { v: "×0,82", k: "провал января", drill: RPT.season.drill(RPT.season.weeks[18]) },
        { v: "3", k: "сценария без истории", drill: { title: "Сценарии A/B/C", value: "A: ниша как есть · B: половина амплитуды · C: плоский план", sub: "Для SKU без собственной сезонности; пересмотр после первых 8 недель факта.", source: "Методика Conwix" } },
        { v: "нед 1", k: "= первая неделя августа", drill: { title: "Сезонный год", value: "Неделя 1 = август", sub: "Конвенция сезонного года: отсчёт от начала подъёма, а не от января.", source: "Методика Conwix" } },
      ] },
    stock: { badge: "§6 · Остатки", title: "Кэш, замороженный на полке", pi: 2,
      curve: { data: RPT.stock.skus.map(s => s.weeks), cats: RPT.stock.skus.map(s => s.name), fmt: v => v.toFixed(1) + " нед", label: "Запас, недель продаж" },
      stats: [
        { v: "976 тыс ₽", k: "заморожено в излишках", drill: { title: "Замороженный кэш", value: "976 тыс ₽ из 1 314 тыс ₽", sub: "Три SKU выше порога 8 недель: «Рюкзак городской», «Плед флисовый», «Коврик для йоги».", source: "Демо-данные Conwix" } },
        { v: "8 нед", k: "порог излишка", drill: { title: "Порог излишка", value: "Запас > 8 недель", sub: "Запас = остаток / средние продажи 4 недель. Выше порога — платное хранение съедает маржу ежедневно.", source: "Методика Conwix" } },
        { v: "цена-пол", k: "пол лесенки скидок", drill: QUOTES.q_conwix_rule && { title: "Правило №1 ликвидации", value: "Ступень = MAX(цена × k; цена-пол)", sub: QUOTES.q_conwix_rule.context_note, source: "Методика Conwix" } },
        { v: "3 SKU", k: "в плане распродажи", drill: { title: "План распродажи", value: "3 SKU-кандидата", sub: "Цена-пол, лесенка скидок, прогноз возврата кэша. Отдельно — кандидаты на консервацию до сезона.", source: "Демо-данные Conwix" } },
      ] },
    how: { badge: "§7 · Продукт", title: "Недельный цикл Conwix", pi: 3,
      curve: { data: [6, 0.5], cats: ["таблицы", "Conwix"], fmt: v => v + " ч", label: "Часов рутины в неделю" },
      stats: [
        { v: "10 мин", k: "первый разбор", drill: { title: "Старт", value: "10 минут", sub: "Выгрузка юнит-экономики файлом или по API; разработчик не нужен.", source: "Методика Conwix" } },
        { v: "30 мин", k: "в неделю вместо 6 ч", drill: { title: "Недельный ритуал", value: "30 минут в понедельник", sub: "Панель показывает только решения: статусы, план распродажи, бюджет, вывод SKU.", source: "Методика Conwix" } },
        { v: "4", k: "шага цикла", drill: { title: "Цикл", value: "Подключи → пересчитай → действуй → проверь дельту", sub: "Дельта неделя к неделе показывает, что сработало.", source: "Методика Conwix" } },
        { v: "0", k: "«магических» прогнозов", drill: { title: "Принцип", value: "Формулы вместо обещаний", sub: "Conwix не предсказывает выручку нейросетью — он показывает, куда уходят деньги.", source: "Методика Conwix" } },
      ] },
    signals: { badge: "§8 · Контроль", title: "Шесть еженедельных триггеров", pi: 3,
      curve: { data: [1, 2, 3, 0, 1, 0], cats: RPT.signals.map(s => s.k), fmt: v => v + " вкл", label: "Активные срабатывания" },
      stats: [
        { v: "6", k: "триггеров на доске", drill: { title: "Доска мониторинга", value: "6 триггеров", sub: "ДРР vs лимит · ДРР vs безубыток · запас в неделях · доля выкупа · Kсезон · сплит бюджета.", source: "Методика Conwix" } },
        { v: "1", k: "СТОП прямо сейчас", drill: RPT.signals[1].d },
        { v: "7 дней", k: "цикл ревизии", drill: { title: "Каденция", value: "Еженедельно", sub: "Любой пробой возвращает к соответствующему разделу и его действию.", source: "Методика Conwix" } },
        { v: "порог+действие", k: "у каждого триггера", drill: { title: "Контракт триггера", value: "Порог → текущее значение → действие", sub: "Триггер без действия — декорация; здесь каждый ведёт к конкретному шагу.", source: "Методика Conwix" } },
      ] },
    cta: { badge: "§9 · Старт", title: "Аудит одной недели", pi: 3,
      curve: { data: RPT.market.gmv, cats: RPT.market.years, fmt: v => v.toFixed(1) + " трлн ₽", label: "Рынок, в котором ты продаёшь" },
      stats: [
        { v: "781 тыс ₽", k: "средний оборот селлера/мес", drill: { title: "Средний оборот селлера", value: "781,2 тыс ₽/мес (+14,2%)", sub: "Медиана — 131,8 тыс ₽ (+36,9%). Данные Q1 2025.", source: "«Точка Маркетплейсы» · «Коммерсантъ», 2025-05" } },
        { v: "10 мин", k: "до первых лимитов", drill: { title: "Первый шаг", value: "Выгрузка → лимиты за 10 минут", sub: "Бесплатный аудит на твоих реальных данных.", source: "Методика Conwix" } },
        { v: "2", k: "платформы: Ozon + WB", drill: { title: "Платформы", value: "Ozon и Wildberries", sub: "77% всех заказов рынка приходится на эту пару.", source: "Data Insight, 2026-03" } },
        { v: "0 ₽", k: "аудит первой недели", drill: { title: "Аудит недели", value: "Бесплатно", sub: "Лимиты ДРР, замороженный кэш, первый план распродажи — на твоих данных.", source: "Conwix" } },
      ] },
  };

  let cur = "frame";
  let hits = [];

  function draw() {
    const E = ERAS[cur]; if (!E) return;
    const { w, h } = view;
    ctx.clearRect(0, 0, w, h);
    hits = [];
    let y = M.t;

    // ── Бейдж раздела ──
    ctx.font = U.font(11, "700", "mono");
    ctx.fillStyle = U.PAL.blue;
    ctx.fillText(E.badge, M.l, y); y += 24;
    // ── Заголовок ──
    ctx.font = U.font(17, "700", "sans");
    ctx.fillStyle = U.PAL.ink;
    wrapText(E.title, M.l, y, w - M.l - M.r, 21); y += 30;

    // ── Шкала цикла недели ──
    const dw = (w - M.l - M.r) / PHASES.length;
    PHASES.forEach((p, i) => {
      const x = M.l + i * dw;
      ctx.fillStyle = i <= E.pi ? U.PAL.blue : U.PAL.line;
      ctx.fillRect(x, y, dw - 6, 3);
      ctx.font = U.font(9.5, i === E.pi ? "700" : "400", "mono");
      ctx.fillStyle = i === E.pi ? U.PAL.ink : U.PAL.inkLo;
      ctx.fillText(p, x, y + 16);
    });
    y += 40;

    // ── Спарклайн ──
    const c = E.curve;
    if (c && c.data && c.data.length) {
      const ch = Math.min(150, h * 0.18);
      const max = Math.max(...c.data), min = Math.min(...c.data, 0);
      const span = max - min || 1;
      const x0 = M.l, x1 = w - M.r, bw = (x1 - x0) / c.data.length;
      ctx.font = U.font(10, "400", "mono");
      ctx.fillStyle = U.PAL.inkLo;
      ctx.fillText(c.label || "", x0, y); y += 8;
      c.data.forEach((v, i) => {
        const bh = (v - min) / span * (ch - 26) + 6;
        ctx.fillStyle = i === c.data.length - 1 ? U.PAL.blue : U.PAL.line;
        ctx.fillRect(x0 + i * bw + bw * 0.2, y + (ch - 20) - bh, bw * 0.6, bh);
      });
      ctx.strokeStyle = U.PAL.line; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.moveTo(x0, y + ch - 20); ctx.lineTo(x1, y + ch - 20); ctx.stroke();
      ctx.fillStyle = U.PAL.blue; ctx.font = U.font(11, "700", "mono");
      const lastLbl = c.fmt(c.data[c.data.length - 1]);
      ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 4;
      ctx.strokeText(lastLbl, x0, y + ch - 2); ctx.fillText(lastLbl, x0, y + ch - 2);
      y += ch + 18;
    }

    // ── Показатели ──
    E.stats.forEach(s => {
      ctx.font = U.font(20, "700", "mono");
      ctx.fillStyle = U.PAL.ink;
      ctx.fillText(s.v, M.l, y + 20);
      ctx.font = U.font(11, "400", "mono");
      ctx.fillStyle = U.PAL.inkLo;
      wrapText(s.k, M.l, y + 38, w - M.l - M.r, 14);
      const boxH = 62;
      ctx.strokeStyle = U.PAL.lineLo; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.moveTo(M.l, y + boxH); ctx.lineTo(w - M.r, y + boxH); ctx.stroke();
      if (s.drill) hits.push({ x: M.l, y: y - 4, w: w - M.l - M.r, h: boxH + 4, drill: s.drill });
      y += boxH + 6;
    });

    // ── Подпись внизу ──
    ctx.font = U.font(9.5, "400", "mono");
    ctx.fillStyle = U.PAL.inkLo;
    ctx.fillText("conwix · недельный цикл", M.l, h - 18);
  }

  function wrapText(text, x, y, maxW, lh) {
    const words = String(text).split(" ");
    let line = "", yy = y;
    words.forEach(wd => {
      const t = line ? line + " " + wd : wd;
      if (ctx.measureText(t).width > maxW && line) { ctx.fillText(line, x, yy); line = wd; yy += lh; }
      else line = t;
    });
    if (line) ctx.fillText(line, x, yy);
  }

  canvas.addEventListener("click", e => {
    const r = canvas.getBoundingClientRect();
    const x = e.clientX - r.left, y = e.clientY - r.top;
    const hit = hits.find(hh => x >= hh.x && x <= hh.x + hh.w && y >= hh.y && y <= hh.y + hh.h);
    if (hit) U.showDrill({ ...hit.drill, x: e.clientX, y: e.clientY });
  });

  function set(win) { if (ERAS[win]) { cur = win; draw(); } }
  return { set, ERAS, redraw: draw };
})();

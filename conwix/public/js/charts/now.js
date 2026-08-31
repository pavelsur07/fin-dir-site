// §3 · Большие цифры (now-grid, count-up в main.js)
(() => {
  const host = document.getElementById("now-grid");
  if (!host) return;
  const head = document.createElement("div");
  head.className = "now-head";
  head.innerHTML =
    `<p class="nw-kick">Точки роста · 2026</p>` +
    `<p class="nw-title">Почему «считать» важнее «расширять» — в трёх цифрах</p>` +
    `<p class="nw-sub">клик по любой цифре — источник</p>`;
  host.appendChild(head);
  RPT.now.forEach(c => {
    const el = document.createElement("div");
    el.className = "now-card";
    el.setAttribute("data-drill-keep", "1");
    el.innerHTML =
      `<div class="n-v">${c.v}</div>` +
      `<div class="n-k">${c.k}</div>` +
      `<div class="n-src">Источник: ${c.src}</div>`;
    el.addEventListener("click", e => U.showDrill({ ...c.d, x: e.clientX, y: e.clientY }));
    host.appendChild(el);
  });
})();

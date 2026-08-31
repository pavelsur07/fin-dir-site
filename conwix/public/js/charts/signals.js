// §8 · Сетка еженедельных триггеров мониторинга
(() => {
  const host = document.getElementById("signal-grid");
  if (!host) return;
  RPT.signals.forEach((s, i) => {
    const el = document.createElement("div");
    el.className = "sig-card";
    el.setAttribute("data-drill-keep", "1");
    el.innerHTML =
      `<p class="sig-no">T${i + 1}</p>` +
      `<p class="sig-k">${s.k}</p>` +
      `<p class="sig-now">${s.now}</p>` +
      `<p class="sig-th">порог: ${s.th}</p>` +
      `<p class="sig-act">→ ${s.act}</p>`;
    el.addEventListener("click", e => U.showDrill({ ...s.d, x: e.clientX, y: e.clientY }));
    host.appendChild(el);
  });
})();

// ═══ Обложка Conwix: «город» из товарных карточек и растущая линия прибыли ═══
// Одна сцена, один смысл: из сетки SKU вырастает прибыль. Без внешних запросов.
(() => {
  const canvas = document.getElementById("cover-canvas");
  if (!canvas) return;
  const { fit } = U.bindCanvas(canvas);
  const ctx = canvas.getContext("2d");
  const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  let view = fit();
  let mx = 0.5, my = 0.5; // параллакс-указатель
  window.addEventListener("resize", () => { view = fit(); });
  window.addEventListener("pointermove", e => {
    mx = e.clientX / window.innerWidth; my = e.clientY / window.innerHeight;
  }, { passive: true });

  const rng = U.makeRng(20260830);
  // Колонны-SKU: высоты в «неделях прибыли», последние — акцентные (точки роста)
  const N = 34;
  const cols = Array.from({ length: N }, (_, i) => {
    const t = i / (N - 1);
    const base = 0.16 + 0.30 * t + rng() * 0.10;       // растущий тренд
    return { t, h: base, ph: rng() * U.TAU, accent: i >= N - 6 };
  });
  // Линия прибыли поверх колонн
  const line = cols.map((c, i) => ({ t: c.t, v: c.h + 0.06 + 0.05 * Math.sin(i * 0.7) }));

  function draw(time) {
    const { w, h } = view;
    ctx.clearRect(0, 0, w, h);
    const px = (mx - 0.5) * 14, py = (my - 0.5) * 10;

    // Точечное поле сверху (полутон)
    U.dotField(ctx, 0, 0, w, h * 0.42, { gap: 16, color: U.PAL.blue, density: (u, v) => (1 - v) * 0.5 });

    const yBase = h * 0.94 + py;
    const x0 = w * 0.06 + px, x1 = w * 0.97 + px;
    const cw = (x1 - x0) / N;
    const scaleH = h * 0.52;

    // Нулевая линия
    ctx.strokeStyle = U.PAL.line; ctx.lineWidth = 1;
    ctx.beginPath(); ctx.moveTo(x0 - 10, yBase); ctx.lineTo(x1 + 10, yBase); ctx.stroke();

    // Колонны
    cols.forEach((c, i) => {
      const pulse = reduced ? 1 : 1 + 0.035 * Math.sin(time / 900 + c.ph);
      const ch = c.h * scaleH * pulse;
      const x = x0 + i * cw;
      ctx.fillStyle = c.accent ? U.PAL.blue : U.PAL.line;
      ctx.globalAlpha = c.accent ? 0.92 : 0.85;
      ctx.fillRect(x + cw * 0.18, yBase - ch, cw * 0.64, ch);
      // «Этикетка» карточки
      ctx.globalAlpha = c.accent ? 0.35 : 0.5;
      ctx.fillStyle = U.PAL.paper;
      ctx.fillRect(x + cw * 0.30, yBase - ch + 6, cw * 0.40, 3);
      ctx.globalAlpha = 1;
    });

    // Линия прибыли
    const prog = reduced ? 1 : Math.min(1, (time % 6000) / 2600);
    ctx.strokeStyle = U.PAL.blue; ctx.lineWidth = 2.4;
    ctx.lineJoin = "round"; ctx.beginPath();
    const nPts = Math.max(2, Math.floor(line.length * prog));
    line.slice(0, nPts).forEach((p, i) => {
      const x = x0 + p.t * (x1 - x0) + cw * 0.5;
      const y = yBase - p.v * scaleH - 16;
      i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
    });
    ctx.stroke();
    // Головная точка
    const hp = line[Math.max(0, nPts - 1)];
    const hx = x0 + hp.t * (x1 - x0) + cw * 0.5, hy = yBase - hp.v * scaleH - 16;
    ctx.fillStyle = U.PAL.blue;
    ctx.beginPath(); ctx.arc(hx, hy, 5, 0, U.TAU); ctx.fill();
    ctx.strokeStyle = "rgba(34,81,255,.35)"; ctx.lineWidth = 10;
    ctx.beginPath(); ctx.arc(hx, hy, 9 + (reduced ? 0 : 3 * Math.sin(time / 300)), 0, U.TAU); ctx.stroke();

    // Подпись у головной точки
    ctx.font = U.font(12, "700", "mono");
    ctx.fillStyle = U.PAL.blue;
    const label = "прибыль ↑";
    const lw = ctx.measureText(label).width;
    ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 5; ctx.strokeText(label, Math.min(hx + 12, w - lw - 12), hy - 8);
    ctx.fillText(label, Math.min(hx + 12, w - lw - 12), hy - 8);
  }

  if (reduced) { draw(6000); return; }
  let raf;
  const loop = t => { draw(t); raf = requestAnimationFrame(loop); };
  raf = requestAnimationFrame(loop);
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) cancelAnimationFrame(raf);
    else raf = requestAnimationFrame(loop);
  });
})();

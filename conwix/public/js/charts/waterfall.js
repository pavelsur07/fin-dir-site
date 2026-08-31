// §2 · Водопад юнит-экономики демо-SKU: от цены до прибыли
(() => {
  const host = document.getElementById("waterfall-chart");
  if (!host) return;
  const body = U.frame(host, {
    title: "От 1 990 ₽ на витрине до 453 ₽ прибыли",
    sub: RPT.waterfall.sku + " · водопад вычетов, ₽ на единицу · клик по ступени — расчёт",
    src: RPT.waterfall.src,
  });
  const canvas = document.createElement("canvas");
  canvas.style.cssText = "width:100%;height:380px;display:block;cursor:pointer";
  body.appendChild(canvas);
  const { fit } = U.bindCanvas(canvas);
  const ctx = canvas.getContext("2d");
  let view = fit();
  window.addEventListener("resize", () => { view = fit(); draw(); });

  const steps = RPT.waterfall.steps;
  let hits = [];
  function draw() {
    const { w, h } = view;
    ctx.clearRect(0, 0, w, h);
    hits = [];
    const M = { l: 52, r: 16, t: 20, b: 64 };
    const x0 = M.l, x1 = w - M.r, y0 = M.t, y1 = h - M.b;
    const maxV = 2000;
    const bw = (x1 - x0) / steps.length;
    const y = v => y1 - (v / maxV) * (y1 - y0);

    // сетка
    ctx.font = U.font(10, "400", "mono"); ctx.fillStyle = U.PAL.inkLo;
    for (let g = 0; g <= maxV; g += 500) {
      ctx.strokeStyle = U.PAL.lineLo;
      ctx.beginPath(); ctx.moveTo(x0, y(g)); ctx.lineTo(x1, y(g)); ctx.stroke();
      ctx.fillText(g.toLocaleString("ru-RU"), 4, y(g) + 3);
    }

    let run = 0;
    steps.forEach((s, i) => {
      const x = x0 + i * bw + bw * 0.14, wBar = bw * 0.72;
      let top, bot, color;
      if (s.type === "start") { run = s.v; top = y(s.v); bot = y(0); color = U.PAL.ink; }
      else if (s.type === "end") { top = y(s.v); bot = y(0); color = U.PAL.blue; }
      else { top = y(run); bot = y(run + s.v); run += s.v; color = s.k.includes("Реклама") ? U.PAL.blue : U.PAL.inkMd; }
      ctx.fillStyle = color; ctx.globalAlpha = s.type === "cost" ? 0.82 : 0.95;
      ctx.fillRect(x, top, wBar, Math.max(2, bot - top));
      ctx.globalAlpha = 1;
      // соединительная пунктирная линия к следующей ступени
      if (i < steps.length - 1) {
        ctx.strokeStyle = U.PAL.inkLo; ctx.setLineDash([3, 3]); ctx.lineWidth = 1;
        const lvl = s.type === "start" ? y(s.v) : (s.type === "end" ? y(s.v) : y(run));
        ctx.beginPath(); ctx.moveTo(x + wBar, lvl); ctx.lineTo(x + bw, lvl); ctx.stroke();
        ctx.setLineDash([]);
      }
      // значение
      ctx.font = U.font(11.5, "700", "mono");
      ctx.fillStyle = s.type === "cost" ? U.PAL.neg : (s.type === "end" ? U.PAL.blue : U.PAL.ink);
      const lbl = (s.type === "cost" ? "−" : "") + Math.abs(s.v).toLocaleString("ru-RU");
      const lx = x + wBar / 2 - ctx.measureText(lbl).width / 2;
      ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 4;
      ctx.strokeText(lbl, lx, top - 6); ctx.fillText(lbl, lx, top - 6);
      // подпись ступени (перенос)
      ctx.font = U.font(9.5, "400", "mono"); ctx.fillStyle = U.PAL.inkMd;
      wrapCenter(s.k, x + wBar / 2, y1 + 16, bw + 6, 12);
      if (s.pct) {
        ctx.fillStyle = U.PAL.inkLo;
        wrapCenter(s.pct, x + wBar / 2, y1 + 16 + (s.k.length > 12 ? 24 : 12), bw + 6, 12);
      }
      hits.push({ x, y: top, w: wBar, h: Math.max(30, bot - top), drill: s.d });
    });
  }
  function wrapCenter(text, cx, yy, maxW, lh) {
    const words = text.split(" ");
    let line = ""; const lines = [];
    words.forEach(wd => {
      const t = line ? line + " " + wd : wd;
      if (ctx.measureText(t).width > maxW && line) { lines.push(line); line = wd; } else line = t;
    });
    if (line) lines.push(line);
    lines.slice(0, 2).forEach((ln, i) => ctx.fillText(ln, cx - ctx.measureText(ln).width / 2, yy + i * lh));
  }
  canvas.addEventListener("click", e => {
    const r = canvas.getBoundingClientRect();
    const x = e.clientX - r.left, y = e.clientY - r.top;
    const hit = hits.find(hh => x >= hh.x - 4 && x <= hh.x + hh.w + 4);
    if (hit) U.showDrill({ ...hit.drill, x: e.clientX, y: e.clientY });
  });
  draw();
})();

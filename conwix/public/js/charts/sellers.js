// §1 · Активные селлеры WB+Ozon: конец экстенсивного роста
(() => {
  const host = document.getElementById("sellers-chart");
  if (!host) return;
  const body = U.frame(host, {
    title: "Продавцов больше не становится — началась консолидация",
    sub: "Активные селлеры Wildberries + Ozon, млн · темп прироста, % г/г · клик по точке — детали",
    src: RPT.sellers.src,
  });
  const canvas = document.createElement("canvas");
  canvas.style.cssText = "width:100%;height:300px;display:block;cursor:pointer";
  body.appendChild(canvas);
  const { fit } = U.bindCanvas(canvas);
  const ctx = canvas.getContext("2d");
  let view = fit();
  window.addEventListener("resize", () => { view = fit(); draw(); });

  const D = RPT.sellers;
  let hits = [];
  function draw() {
    const { w, h } = view;
    ctx.clearRect(0, 0, w, h);
    hits = [];
    const M = { l: 40, r: 20, t: 30, b: 60 };
    const x0 = M.l, x1 = w - M.r, y0 = M.t, y1 = h - M.b;
    const maxV = 1.4;
    const step = (x1 - x0) / (D.years.length - 1);

    // сетка
    ctx.font = U.font(10, "400", "mono"); ctx.fillStyle = U.PAL.inkLo;
    for (let g = 0; g <= 1.4; g += 0.35) {
      const y = y1 - (g / maxV) * (y1 - y0);
      ctx.strokeStyle = U.PAL.lineLo;
      ctx.beginPath(); ctx.moveTo(x0, y); ctx.lineTo(x1, y); ctx.stroke();
      ctx.fillText(g.toFixed(2).replace(".", ","), 2, y + 3);
    }

    // заливка под кривой
    ctx.beginPath();
    D.count.forEach((v, i) => {
      const x = x0 + i * step, y = y1 - (v / maxV) * (y1 - y0);
      i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
    });
    ctx.lineTo(x1, y1); ctx.lineTo(x0, y1); ctx.closePath();
    ctx.fillStyle = "rgba(34,81,255,.06)"; ctx.fill();

    // кривая
    ctx.strokeStyle = U.PAL.ink; ctx.lineWidth = 2.4; ctx.beginPath();
    D.count.forEach((v, i) => {
      const x = x0 + i * step, y = y1 - (v / maxV) * (y1 - y0);
      i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
    });
    ctx.stroke();

    D.count.forEach((v, i) => {
      const x = x0 + i * step, y = y1 - (v / maxV) * (y1 - y0);
      const neg = D.growth[i] < 0;
      ctx.fillStyle = U.PAL.paper; ctx.beginPath(); ctx.arc(x, y, 7, 0, U.TAU); ctx.fill();
      ctx.fillStyle = neg ? U.PAL.neg : U.PAL.blue;
      ctx.beginPath(); ctx.arc(x, y, 4.5, 0, U.TAU); ctx.fill();
      ctx.font = U.font(11, "700", "mono"); ctx.fillStyle = U.PAL.ink;
      const lbl = String(v).replace(".", ",");
      ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 4;
      ctx.strokeText(lbl, x - ctx.measureText(lbl).width / 2, y - 14);
      ctx.fillText(lbl, x - ctx.measureText(lbl).width / 2, y - 14);
      // темп
      ctx.font = U.font(10, "400", "mono");
      ctx.fillStyle = neg ? U.PAL.neg : U.PAL.inkLo;
      const g = (D.growth[i] > 0 ? "+" : "") + D.growth[i] + "%";
      ctx.strokeText(g, x - ctx.measureText(g).width / 2, y + 22);
      ctx.fillText(g, x - ctx.measureText(g).width / 2, y + 22);
      ctx.fillStyle = U.PAL.inkMd; ctx.font = U.font(11, "400", "mono");
      ctx.fillText(D.years[i], x - 14, y1 + 20);
      hits.push({ x: x - step / 2, y: 0, w: step, h: h, drill: D.drills[D.years[i]] });
    });

    // аннотация разворота
    ctx.font = U.font(10.5, "700", "mono"); ctx.fillStyle = U.PAL.neg;
    const note = "впервые без притока новых игроков";
    ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 4;
    ctx.strokeText(note, x1 - ctx.measureText(note).width, y0 + 4);
    ctx.fillText(note, x1 - ctx.measureText(note).width, y0 + 4);
  }
  canvas.addEventListener("click", e => {
    const r = canvas.getBoundingClientRect();
    const x = e.clientX - r.left, y = e.clientY - r.top;
    const hit = hits.find(hh => x >= hh.x && x <= hh.x + hh.w);
    if (hit) U.showDrill({ ...hit.drill, x: e.clientX, y: e.clientY });
  });
  draw();
})();

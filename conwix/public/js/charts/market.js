// §1 · Рынок интернет-торговли РФ: объём (столбцы) + доля онлайна в рознице (линия)
(() => {
  const host = document.getElementById("market-chart");
  if (!host) return;
  const body = U.frame(host, {
    title: "Рынок растёт двузначными темпами третий год подряд",
    sub: "Объём интернет-торговли РФ, трлн ₽ · доля онлайна в рознице, % · клик по столбцу — детали",
    src: RPT.market.src,
  });
  const canvas = document.createElement("canvas");
  canvas.style.cssText = "width:100%;height:340px;display:block;cursor:pointer";
  body.appendChild(canvas);
  const { fit } = U.bindCanvas(canvas);
  const ctx = canvas.getContext("2d");
  let view = fit();
  window.addEventListener("resize", () => { view = fit(); draw(); });

  const D = RPT.market;
  let hits = [];
  function draw() {
    const { w, h } = view;
    ctx.clearRect(0, 0, w, h);
    hits = [];
    const M = { l: 46, r: 46, t: 26, b: 46 };
    const x0 = M.l, x1 = w - M.r, y0 = M.t, y1 = h - M.b;
    const maxV = 13;
    const bw = (x1 - x0) / D.years.length;

    // Сетка
    ctx.font = U.font(10, "400", "mono"); ctx.fillStyle = U.PAL.inkLo;
    for (let g = 0; g <= maxV; g += 4) {
      const y = y1 - (g / maxV) * (y1 - y0);
      ctx.strokeStyle = U.PAL.lineLo; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.moveTo(x0, y); ctx.lineTo(x1, y); ctx.stroke();
      ctx.fillText(g + "", 8, y + 3);
    }
    ctx.fillText("трлн ₽", 8, y0 - 8);
    ctx.fillText("% розницы", w - M.r - 4, y0 - 8);

    // Столбцы GMV
    D.gmv.forEach((v, i) => {
      const x = x0 + i * bw + bw * 0.22, wBar = bw * 0.56;
      const bh = (v / maxV) * (y1 - y0);
      const y = y1 - bh;
      ctx.fillStyle = i === D.gmv.length - 1 ? U.PAL.blue : U.PAL.ink;
      ctx.globalAlpha = i === D.gmv.length - 1 ? 0.95 : 0.88;
      ctx.fillRect(x, y, wBar, bh);
      ctx.globalAlpha = 1;
      ctx.font = U.font(13, "700", "mono"); ctx.fillStyle = U.PAL.ink;
      const lbl = String(v).replace(".", ",");
      ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 4;
      ctx.strokeText(lbl, x + wBar / 2 - ctx.measureText(lbl).width / 2, y - 8);
      ctx.fillText(lbl, x + wBar / 2 - ctx.measureText(lbl).width / 2, y - 8);
      // дельта
      if (i > 0) {
        const d = Math.round((v / D.gmv[i - 1] - 1) * 100);
        ctx.font = U.font(10, "400", "mono"); ctx.fillStyle = U.PAL.blue;
        ctx.fillText("+" + d + "%", x + wBar / 2 - 14, y - 24);
      }
      ctx.font = U.font(11, "400", "mono"); ctx.fillStyle = U.PAL.inkMd;
      ctx.fillText(D.years[i], x + wBar / 2 - 14, y1 + 18);
      hits.push({ x, y, w: wBar, h: bh, drill: D.drills[D.years[i]] });
    });

    // Линия доли онлайна (правая шкала 0–25%)
    ctx.strokeStyle = U.PAL.blueLo; ctx.lineWidth = 2; ctx.beginPath();
    D.onlineShare.forEach((v, i) => {
      const x = x0 + i * bw + bw * 0.5;
      const y = y1 - (v / 25) * (y1 - y0);
      i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
    });
    ctx.stroke();
    D.onlineShare.forEach((v, i) => {
      const x = x0 + i * bw + bw * 0.5;
      const y = y1 - (v / 25) * (y1 - y0);
      ctx.fillStyle = U.PAL.paper; ctx.beginPath(); ctx.arc(x, y, 6, 0, U.TAU); ctx.fill();
      ctx.fillStyle = U.PAL.blue; ctx.beginPath(); ctx.arc(x, y, 3.5, 0, U.TAU); ctx.fill();
      ctx.font = U.font(10, "700", "mono");
      const lbl = String(v).replace(".", ",") + "%";
      ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 4;
      ctx.strokeText(lbl, x - ctx.measureText(lbl).width / 2, y - 12);
      ctx.fillText(lbl, x - ctx.measureText(lbl).width / 2, y - 12);
    });
    // Легенда
    ctx.font = U.font(10.5, "400", "mono");
    ctx.fillStyle = U.PAL.ink; ctx.fillRect(x0, h - 16, 10, 10);
    ctx.fillText("объём рынка, трлн ₽", x0 + 16, h - 7);
    ctx.fillStyle = U.PAL.blue; ctx.beginPath(); ctx.arc(x0 + 190, h - 11, 4, 0, U.TAU); ctx.fill();
    ctx.fillText("доля онлайна в рознице, %", x0 + 200, h - 7);
  }
  canvas.addEventListener("click", e => {
    const r = canvas.getBoundingClientRect();
    const x = e.clientX - r.left, y = e.clientY - r.top;
    const hit = hits.find(hh => x >= hh.x && x <= hh.x + hh.w && y >= hh.y && y <= y1Limit(hh));
    if (hit) U.showDrill({ ...hit.drill, x: e.clientX, y: e.clientY });
  });
  function y1Limit() { return 1e9; }
  draw();
})();

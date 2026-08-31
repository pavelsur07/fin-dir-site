// §6 · Запас в неделях по SKU и замороженный кэш
(() => {
  const host = document.getElementById("stock-chart");
  if (!host) return;
  const body = U.frame(host, {
    title: "976 тыс ₽ из 1 314 тыс ₽ остатков лежат дольше восьми недель",
    sub: "Запас в неделях продаж (бары) · замороженный кэш, тыс ₽ (подпись) · клик по строке — действие",
    src: RPT.stock.src,
  });
  const canvas = document.createElement("canvas");
  canvas.style.cssText = "width:100%;display:block;cursor:pointer";
  canvas.style.height = (RPT.stock.skus.length * 42 + 76) + "px";
  body.appendChild(canvas);
  const { fit } = U.bindCanvas(canvas);
  const ctx = canvas.getContext("2d");
  let view = fit();
  window.addEventListener("resize", () => { view = fit(); draw(); });

  const skus = RPT.stock.skus;
  let hits = [];
  function draw() {
    const { w, h } = view;
    ctx.clearRect(0, 0, w, h);
    hits = [];
    const M = { l: 170, r: 90, t: 24, b: 46 };
    const x0 = M.l, x1 = w - M.r, y0 = M.t;
    const maxV = 15;
    const rowH = (h - M.t - M.b) / skus.length;
    const X = v => x0 + (v / maxV) * (x1 - x0);

    // зона излишка
    ctx.fillStyle = "rgba(194,47,78,.06)";
    ctx.fillRect(X(RPT.stock.threshold), y0 - 4, x1 - X(RPT.stock.threshold), h - M.b - y0 + 4);
    ctx.font = U.font(9.5, "700", "mono"); ctx.fillStyle = U.PAL.neg;
    ctx.fillText("ИЗЛИШЕК > 8 НЕД", X(RPT.stock.threshold) + 6, y0 + 6);

    ctx.font = U.font(10, "400", "mono"); ctx.fillStyle = U.PAL.inkLo;
    for (let g = 0; g <= maxV; g += 5) {
      ctx.strokeStyle = U.PAL.lineLo;
      ctx.beginPath(); ctx.moveTo(X(g), y0); ctx.lineTo(X(g), h - M.b); ctx.stroke();
      ctx.fillText(g + " нед", X(g) - 14, h - M.b + 16);
    }

    skus.forEach((s, i) => {
      const cy = y0 + i * rowH + rowH / 2;
      const over = s.weeks > RPT.stock.threshold;
      const bw = X(s.weeks) - x0;
      ctx.fillStyle = over ? U.PAL.neg : U.PAL.blue;
      ctx.globalAlpha = over ? 0.85 : 0.8;
      ctx.fillRect(x0, cy - 8, bw, 16);
      ctx.globalAlpha = 1;
      // пороговая черта
      ctx.strokeStyle = U.PAL.ink; ctx.lineWidth = 1.6; ctx.setLineDash([4, 3]);
      ctx.beginPath(); ctx.moveTo(X(RPT.stock.threshold), cy - 11); ctx.lineTo(X(RPT.stock.threshold), cy + 11); ctx.stroke();
      ctx.setLineDash([]);
      // значения
      ctx.font = U.font(10.5, "700", "mono"); ctx.fillStyle = over ? U.PAL.neg : U.PAL.ink;
      const lbl = String(s.weeks).replace(".", ",") + " нед";
      ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 4;
      ctx.strokeText(lbl, X(s.weeks) + 8, cy + 4); ctx.fillText(lbl, X(s.weeks) + 8, cy + 4);
      ctx.font = U.font(9.5, "400", "mono"); ctx.fillStyle = U.PAL.inkLo;
      const cash = s.cash + " тыс ₽";
      ctx.fillText(cash, x1 + 10, cy + 3);
      // имя
      ctx.font = U.font(11, "400", "mono"); ctx.fillStyle = U.PAL.ink;
      const nm = s.name.length > 20 ? s.name.slice(0, 19) + "…" : s.name;
      ctx.fillText(nm, 6, cy + 4);
      hits.push({ x: 0, y: cy - rowH / 2, w, h: rowH, drill: RPT.stock.drill(s) });
    });

    // итог
    ctx.font = U.font(10.5, "700", "mono"); ctx.fillStyle = U.PAL.ink;
    ctx.fillText("Кэш в остатках →", x1 - 118, h - M.b + 16);
    ctx.fillStyle = U.PAL.inkLo; ctx.font = U.font(10, "400", "mono");
    ctx.fillText("тыс ₽", x1 + 10, h - M.b + 16);
  }
  canvas.addEventListener("click", e => {
    const r = canvas.getBoundingClientRect();
    const y = e.clientY - r.top;
    const hit = hits.find(hh => y >= hh.y && y <= hh.y + hh.h);
    if (hit) U.showDrill({ ...hit.drill, x: e.clientX, y: e.clientY });
  });
  draw();
})();

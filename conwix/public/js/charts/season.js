// §5 · Kсезон: 52 недели сезонного года (неделя 1 = первая неделя августа)
(() => {
  const host = document.getElementById("season-chart");
  if (!host) return;
  const body = U.frame(host, {
    title: "Сезонная кривая ниши: от августовского старта к декабрьскому пику",
    sub: "Kсезон по неделям сезонного года · иллюстративный ряд (MPSTAT-стиль) · клик по точке — значение",
    src: RPT.season.src,
  });
  const canvas = document.createElement("canvas");
  canvas.style.cssText = "width:100%;height:340px;display:block;cursor:pointer";
  body.appendChild(canvas);
  const { fit } = U.bindCanvas(canvas);
  const ctx = canvas.getContext("2d");
  let view = fit();
  window.addEventListener("resize", () => { view = fit(); draw(); });

  const W = RPT.season.weeks;
  let hits = [];
  const months = ["авг", "сен", "окт", "ноя", "дек", "янв", "фев", "мар", "апр", "май", "июн", "июл"];

  function draw() {
    const { w, h } = view;
    ctx.clearRect(0, 0, w, h);
    hits = [];
    const M = { l: 40, r: 16, t: 26, b: 44 };
    const x0 = M.l, x1 = w - M.r, y0 = M.t, y1 = h - M.b;
    const maxV = 1.7, minV = 0.7;
    const X = i => x0 + (i / (W.length - 1)) * (x1 - x0);
    const Y = v => y1 - ((v - minV) / (maxV - minV)) * (y1 - y0);

    // сетка + линия K=1
    ctx.font = U.font(10, "400", "mono"); ctx.fillStyle = U.PAL.inkLo;
    for (let g = 0.8; g <= 1.6; g += 0.2) {
      ctx.strokeStyle = Math.abs(g - 1) < 0.01 ? U.PAL.inkLo : U.PAL.lineLo;
      ctx.lineWidth = Math.abs(g - 1) < 0.01 ? 1.4 : 1;
      ctx.setLineDash(Math.abs(g - 1) < 0.01 ? [5, 4] : []);
      ctx.beginPath(); ctx.moveTo(x0, Y(g)); ctx.lineTo(x1, Y(g)); ctx.stroke();
      ctx.setLineDash([]);
      ctx.fillText("×" + g.toFixed(1).replace(".", ","), 2, Y(g) + 3);
    }

    // заливка и линия
    ctx.beginPath();
    W.forEach((p, i) => i ? ctx.lineTo(X(i), Y(p.v)) : ctx.moveTo(X(i), Y(p.v)));
    ctx.strokeStyle = U.PAL.blue; ctx.lineWidth = 2.4; ctx.stroke();
    ctx.lineTo(X(W.length - 1), y1); ctx.lineTo(X(0), y1); ctx.closePath();
    ctx.fillStyle = "rgba(34,81,255,.06)"; ctx.fill();

    // точки с отметками
    W.forEach((p, i) => {
      if (!p.mark) return;
      const x = X(i), y = Y(p.v);
      ctx.fillStyle = U.PAL.paper; ctx.beginPath(); ctx.arc(x, y, 6, 0, U.TAU); ctx.fill();
      ctx.fillStyle = U.PAL.blue; ctx.beginPath(); ctx.arc(x, y, 4, 0, U.TAU); ctx.fill();
      ctx.font = U.font(9.5, "700", "mono"); ctx.fillStyle = U.PAL.ink;
      const lw = ctx.measureText(p.mark).width;
      const lx = U.clamp(x - lw / 2, 2, w - lw - 2);
      ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 4;
      ctx.strokeText(p.mark, lx, y - 12); ctx.fillText(p.mark, lx, y - 12);
    });
    // хиты — все недели
    W.forEach((p, i) => {
      const x = X(i), y = Y(p.v);
      hits.push({ x: x - 8, y: y - 8, w: 16, h: 16, drill: RPT.season.drill(p) });
    });

    // месяцы
    ctx.font = U.font(10, "400", "mono"); ctx.fillStyle = U.PAL.inkMd;
    months.forEach((m, i) => {
      const wx = x0 + ((i * 4.33 + 2) / (W.length - 1)) * (x1 - x0);
      ctx.fillText(m, wx - 10, y1 + 18);
    });
  }
  canvas.addEventListener("click", e => {
    const r = canvas.getBoundingClientRect();
    const x = e.clientX - r.left, y = e.clientY - r.top;
    let best = null, bd = 1e9;
    hits.forEach(hh => {
      const d = Math.hypot(x - (hh.x + 8), y - (hh.y + 8));
      if (d < bd) { bd = d; best = hh; }
    });
    if (best && bd < 30) U.showDrill({ ...best.drill, x: e.clientX, y: e.clientY });
  });
  draw();
})();

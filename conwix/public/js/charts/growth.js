// §3 · Карта точек роста: эффект на маржу (п.п.) × усилия (дни) · размер = скорость результата
(() => {
  const host = document.getElementById("growth-chart");
  if (!host) return;
  const body = U.frame(host, {
    title: "Что делать на этой неделе: карта «эффект × усилия»",
    sub: "X — усилия внедрения, дни · Y — типичный эффект на маржу, п.п. · клик по точке — состав эффекта",
    src: RPT.growth.src,
  });
  const canvas = document.createElement("canvas");
  canvas.style.cssText = "width:100%;height:400px;display:block;cursor:pointer";
  body.appendChild(canvas);
  const { fit } = U.bindCanvas(canvas);
  const ctx = canvas.getContext("2d");
  let view = fit();
  window.addEventListener("resize", () => { view = fit(); draw(); });

  const pts = RPT.growth.points;
  let hits = [];
  function draw() {
    const { w, h } = view;
    ctx.clearRect(0, 0, w, h);
    hits = [];
    const M = { l: 52, r: 20, t: 22, b: 48 };
    const x0 = M.l, x1 = w - M.r, y0 = M.t, y1 = h - M.b;
    const X = v => x0 + (v / 10) * (x1 - x0);
    const Y = v => y1 - (v / 5) * (y1 - y0);

    // зона «сделать первым»
    ctx.fillStyle = "rgba(34,81,255,.05)";
    ctx.fillRect(x0, y0, X(4.5) - x0, Y(2.5) - y0);
    ctx.font = U.font(10, "700", "mono"); ctx.fillStyle = U.PAL.blue;
    ctx.fillText("СДЕЛАТЬ ПЕРВЫМ", x0 + 10, y0 + 16);

    // сетка
    ctx.font = U.font(10, "400", "mono"); ctx.fillStyle = U.PAL.inkLo;
    for (let g = 0; g <= 10; g += 2) {
      ctx.strokeStyle = U.PAL.lineLo;
      ctx.beginPath(); ctx.moveTo(X(g), y0); ctx.lineTo(X(g), y1); ctx.stroke();
      ctx.fillText(g + "", X(g) - 3, y1 + 16);
    }
    for (let g = 0; g <= 5; g += 1) {
      ctx.strokeStyle = U.PAL.lineLo;
      ctx.beginPath(); ctx.moveTo(x0, Y(g)); ctx.lineTo(x1, Y(g)); ctx.stroke();
      ctx.fillText("+" + g, 20, Y(g) + 3);
    }
    ctx.fillText("усилия, дни →", x0, h - 8);
    ctx.save(); ctx.translate(12, y0 + 96); ctx.rotate(-Math.PI / 2);
    ctx.fillText("эффект на маржу, п.п. →", 0, 0); ctx.restore();

    pts.forEach(p => {
      const x = X(p.x), y = Y(p.y);
      const first = p.tag === "сделать первым";
      ctx.fillStyle = first ? "rgba(34,81,255,.16)" : "rgba(5,28,44,.06)";
      ctx.beginPath(); ctx.arc(x, y, p.r, 0, U.TAU); ctx.fill();
      ctx.fillStyle = first ? U.PAL.blue : U.PAL.inkMd;
      ctx.beginPath(); ctx.arc(x, y, Math.max(4, p.r * 0.32), 0, U.TAU); ctx.fill();
      // подпись
      ctx.font = U.font(11, "700", "mono"); ctx.fillStyle = U.PAL.ink;
      const lw = ctx.measureText(p.label).width;
      const lx = U.clamp(x - lw / 2, 4, w - lw - 4);
      ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 4;
      ctx.strokeText(p.label, lx, y - p.r - 8);
      ctx.fillText(p.label, lx, y - p.r - 8);
      ctx.font = U.font(9.5, "400", "mono"); ctx.fillStyle = U.PAL.inkLo;
      const sub = `+${String(p.y).replace(".", ",")} п.п. · ${p.x} дн`;
      ctx.strokeText(sub, U.clamp(x - ctx.measureText(sub).width / 2, 4, w - 90), y + p.r + 14);
      ctx.fillText(sub, U.clamp(x - ctx.measureText(sub).width / 2, 4, w - 90), y + p.r + 14);
      hits.push({ x: x - p.r, y: y - p.r, w: p.r * 2, h: p.r * 2, drill: p.d });
    });
  }
  canvas.addEventListener("click", e => {
    const r = canvas.getBoundingClientRect();
    const x = e.clientX - r.left, y = e.clientY - r.top;
    const hit = hits.find(hh => x >= hh.x && x <= hh.x + hh.w && y >= hh.y && y <= hh.y + hh.h);
    if (hit) U.showDrill({ ...hit.drill, x: e.clientX, y: e.clientY });
  });
  draw();
})();

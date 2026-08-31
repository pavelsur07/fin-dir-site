// §4 · Лимиты ДРР по SKU: факт vs лимит vs безубыток (горизонтальные бары)
(() => {
  const host = document.getElementById("drr-chart");
  if (!host) return;
  const body = U.frame(host, {
    title: "Один SKU прожигает кэш прямо сейчас",
    sub: RPT.drr.note + " · ДРР: факт (точка) · лимит (чёрта) · безубыток (красная зона) · клик по строке — расчёт",
    src: RPT.drr.src,
  });
  const canvas = document.createElement("canvas");
  canvas.style.cssText = "width:100%;display:block;cursor:pointer";
  canvas.style.height = (RPT.drr.skus.length * 44 + 70) + "px";
  body.appendChild(canvas);
  const { fit } = U.bindCanvas(canvas);
  const ctx = canvas.getContext("2d");
  let view = fit();
  window.addEventListener("resize", () => { view = fit(); draw(); });

  const skus = RPT.drr.skus;
  let hits = [];
  const statusColor = s => s.fact > s.be ? U.PAL.neg : (s.fact > s.limit ? U.PAL.copper : U.PAL.green);

  function draw() {
    const { w, h } = view;
    ctx.clearRect(0, 0, w, h);
    hits = [];
    const M = { l: 170, r: 56, t: 26, b: 46 };
    const x0 = M.l, x1 = w - M.r, y0 = M.t;
    const maxV = 26;
    const rowH = (h - M.t - M.b) / skus.length;
    const X = v => x0 + (v / maxV) * (x1 - x0);

    // шкала
    ctx.font = U.font(10, "400", "mono"); ctx.fillStyle = U.PAL.inkLo;
    for (let g = 0; g <= maxV; g += 5) {
      ctx.strokeStyle = U.PAL.lineLo;
      ctx.beginPath(); ctx.moveTo(X(g), y0 - 6); ctx.lineTo(X(g), h - M.b); ctx.stroke();
      ctx.fillText(g + "%", X(g) - 10, h - M.b + 16);
    }

    skus.forEach((s, i) => {
      const cy = y0 + i * rowH + rowH / 2;
      // красная зона за безубытком
      ctx.fillStyle = "rgba(194,47,78,.07)";
      ctx.fillRect(X(s.be), cy - rowH / 2 + 3, x1 - X(s.be), rowH - 6);
      // дорожка до безубытка
      ctx.fillStyle = U.PAL.lineLo;
      ctx.fillRect(x0, cy - 7, X(s.be) - x0, 14);
      // лимит — вертикальная черта
      ctx.strokeStyle = U.PAL.ink; ctx.lineWidth = 2.5;
      ctx.beginPath(); ctx.moveTo(X(s.limit), cy - 12); ctx.lineTo(X(s.limit), cy + 12); ctx.stroke();
      // факт — точка
      const col = statusColor(s);
      ctx.fillStyle = col;
      ctx.beginPath(); ctx.arc(X(s.fact), cy, 6.5, 0, U.TAU); ctx.fill();
      ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 2;
      ctx.beginPath(); ctx.arc(X(s.fact), cy, 6.5, 0, U.TAU); ctx.stroke();
      // имя и статус
      ctx.font = U.font(11.5, "400", "mono"); ctx.fillStyle = U.PAL.ink;
      const nm = s.name.length > 20 ? s.name.slice(0, 19) + "…" : s.name;
      ctx.fillText(nm, 6, cy - 2);
      ctx.font = U.font(9.5, "700", "mono"); ctx.fillStyle = col;
      ctx.fillText(RPT.drr.status(s), 6, cy + 12);
      // значение факта
      ctx.font = U.font(10.5, "700", "mono");
      const lbl = s.fact.toFixed(1).replace(".", ",") + "%";
      ctx.strokeStyle = U.PAL.paper; ctx.lineWidth = 4;
      const lx = Math.min(X(s.fact) + 12, w - 44);
      ctx.strokeText(lbl, lx, cy + 4); ctx.fillText(lbl, lx, cy + 4);
      hits.push({ x: 0, y: cy - rowH / 2, w, h: rowH, drill: RPT.drr.drill(s) });
    });

    // легенда
    ctx.font = U.font(10, "400", "mono");
    let lx = x0; const ly = h - 12;
    ctx.fillStyle = U.PAL.green; ctx.beginPath(); ctx.arc(lx + 4, ly - 3, 4, 0, U.TAU); ctx.fill();
    ctx.fillStyle = U.PAL.inkMd; ctx.fillText("ОК", lx + 12, ly); lx += 44;
    ctx.fillStyle = U.PAL.copper; ctx.beginPath(); ctx.arc(lx + 4, ly - 3, 4, 0, U.TAU); ctx.fill();
    ctx.fillStyle = U.PAL.inkMd; ctx.fillText("ВНИМАНИЕ", lx + 12, ly); lx += 86;
    ctx.fillStyle = U.PAL.neg; ctx.beginPath(); ctx.arc(lx + 4, ly - 3, 4, 0, U.TAU); ctx.fill();
    ctx.fillStyle = U.PAL.inkMd; ctx.fillText("СТОП", lx + 12, ly); lx += 60;
    ctx.strokeStyle = U.PAL.ink; ctx.lineWidth = 2;
    ctx.beginPath(); ctx.moveTo(lx + 4, ly - 9); ctx.lineTo(lx + 4, ly + 3); ctx.stroke();
    ctx.fillStyle = U.PAL.inkMd; ctx.fillText("лимит", lx + 10, ly); lx += 62;
    ctx.fillStyle = "rgba(194,47,78,.25)"; ctx.fillRect(lx, ly - 9, 12, 12);
    ctx.fillStyle = U.PAL.inkMd; ctx.fillText("зона убытка", lx + 18, ly);
  }
  canvas.addEventListener("click", e => {
    const r = canvas.getBoundingClientRect();
    const x = e.clientX - r.left, y = e.clientY - r.top;
    const hit = hits.find(hh => y >= hh.y && y <= hh.y + hh.h);
    if (hit) U.showDrill({ ...hit.drill, x: e.clientX, y: e.clientY });
  });
  draw();
})();

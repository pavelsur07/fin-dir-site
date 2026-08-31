// Orchestration: scroll engine (IntersectionObserver) · era rail · pull-quotes · source buttons
(() => {
  // ── Pull-quote rendering (institutional register: quote → attribution → context / why it matters)
  //    English edition: renders the original quote only, with no translation line.
  document.querySelectorAll(".quote-card").forEach(el => {
    const k = el.dataset.quote;
    const q = QUOTES[k];
    if (!q) return;
    const who = q.speaker_or_source || q.speaker || "—";
    el.innerHTML = `
      <p class="q-text">${q.quote}</p>
      <p class="q-who">— <b>${who}</b>${q.published_at || q.date ? `, ${q.published_at || q.date}` : ""}</p>
      <div class="q-why"><p class="q-wline"><span class="q-lab">Контекст</span> ${q.context_note}</p>
      <p class="q-wline"><span class="q-lab why">Почему это важно</span> ${q.why_it_matters}${q.source_url ? ` <a class="q-src" href="${q.source_url}" target="_blank" rel="noreferrer">Источник ↗</a>` : ""}</p></div>`;
  });

  // ── ◆ 出处按钮:绑定在 sources.js(含 stopPropagation)──

  // ── 封面章节 chips ──
  document.querySelectorAll(".chip[data-goto]").forEach(c => {
    c.onclick = () => document.querySelector(c.dataset.goto)?.scrollIntoView({ behavior: "smooth" });
  });

  // ── 章节滚动引擎（data-win → 全站常驻仪表盘 + 窗口轨） ──
  const winSecs = [...document.querySelectorAll("section[data-win]")];
  let activeWin = null;
  const winObs = new IntersectionObserver(entries => {
    entries.forEach(en => {
      if (en.isIntersecting) {
        activeWin = en.target.dataset.win;
        winSecs.forEach(x => x.classList.remove("active-step"));
        en.target.classList.add("active-step");
        DASH.set(activeWin);
        setRail(activeWin);
      }
    });
  }, { rootMargin: "-38% 0px -38% 0px" });
  winSecs.forEach(s => winObs.observe(s));

  // ── Right rail: scroll-linked gradual reveal from the cover into the chapters ──
  // No 45% threshold jump: opacity rises continuously with how far the cover's bottom edge has scrolled past the viewport.
  const dashRail = document.getElementById("dash-rail");
  const coverEl = document.getElementById("cover");
  if (dashRail && coverEl) {
    const clamp01 = v => Math.min(1, Math.max(0, v));
    const updRail = () => {
      const vh = window.innerHeight;
      const b = coverEl.getBoundingClientRect().bottom;
      const p = clamp01((vh * 0.8 - b) / (vh * 0.5)); // 封面底边从 80%vh 滚到 30%vh 的过程中 0→1
      const s = p * p * (3 - 2 * p); // smoothstep，两端更柔
      dashRail.style.opacity = s.toFixed(3);
      dashRail.style.visibility = s > 0 ? "visible" : "hidden";
      dashRail.style.transform = s >= 1 ? "none" : `translateX(${((1 - s) * 18).toFixed(1)}px)`;
      dashRail.style.pointerEvents = s > 0.9 ? "auto" : "none";
    };
    window.addEventListener("scroll", updRail, { passive: true });
    window.addEventListener("resize", updRail);
    updRail();
  }

  // ── Рельс прогресса по разделам ──
  const rail = document.getElementById("era-rail");
  const track = document.getElementById("rail-track");
  const railYear = document.getElementById("rail-year");
  const ERA_SEGS = [
    { id: "frame", label: "§1 Рынок" },
    { id: "leaks", label: "§2 Юнит" },
    { id: "growth", label: "§3 Рост" },
    { id: "drr", label: "§4 ДРР" },
    { id: "season", label: "§5 Сезон" },
    { id: "stock", label: "§6 Остатки" },
    { id: "how", label: "§7 Продукт" },
    { id: "signals", label: "§8 Контроль" },
    { id: "cta", label: "§9 Старт" },
  ];
  const KNOWN = new Set(ERA_SEGS.map(s => s.id));
  ERA_SEGS.forEach((sg, i) => {
    const seg = document.createElement("div");
    seg.className = "seg"; seg.dataset.id = sg.id;
    const p0 = i / ERA_SEGS.length;
    const p1 = (i + 1) / ERA_SEGS.length;
    seg.style.left = (p0 * 100) + "%";
    seg.style.width = ((p1 - p0) * 100 - 0.8) + "%";
    seg.innerHTML = `<span class="seg-label">${sg.label.split(" ")[0]}</span>`;
    seg.onclick = () => {
      const target = winSecs.find(x => x.dataset.win === sg.id);
      target?.scrollIntoView({ behavior: "smooth", block: "start" });
    };
    track.appendChild(seg);
  });
  const cursor = document.createElement("div");
  Object.assign(cursor.style, {
    position: "absolute", top: "15px", width: "7px", height: "7px",
    background: U.PAL.red, transform: "rotate(45deg)",
    left: "0%", opacity: "0", pointerEvents: "none",
    transition: "left .55s cubic-bezier(.22,.61,.36,1), opacity .3s",
  });
  track.appendChild(cursor);
  track.querySelectorAll(".seg").forEach(sg => {
    sg.style.transition = "background .4s, height .4s, top .4s";
  });

  function setRail(win) {
    const E = DASH.ERAS?.[win];
    if (E) railYear.textContent = E.badge;
    const isWin = KNOWN.has(win);
    rail.classList.toggle("on", isWin);
    track.querySelectorAll(".seg").forEach(sg => sg.classList.toggle("active", sg.dataset.id === win));
    const act = track.querySelector(".seg.active");    if (act) {
      const c = parseFloat(act.style.left) + parseFloat(act.style.width) / 2;
      cursor.style.left = `calc(${c}% - 3.5px)`;
      cursor.style.opacity = "1";
    } else cursor.style.opacity = "0";
  }

  // ── 键盘 ← → 章节跳转 ──
  document.addEventListener("keydown", e => {
    if (e.key !== "ArrowLeft" && e.key !== "ArrowRight") return;
    if (e.metaKey || e.ctrlKey || e.altKey) return;
    const tag = (e.target.tagName || "").toLowerCase();
    if (tag === "input" || tag === "textarea" || e.target.isContentEditable) return;
    const cur = winSecs.find(x => x.classList.contains("active-step")) || winSecs[0];
    const next = winSecs[winSecs.indexOf(cur) + (e.key === "ArrowRight" ? 1 : -1)];
    if (!next) return;
    e.preventDefault();
    next.scrollIntoView({ behavior: "smooth", block: "start" });
  });

  // ── 顶部阅读进度条 ──
  const prog = document.createElement("div");
  prog.id = "read-progress";
  Object.assign(prog.style, {
    position: "fixed", top: "0", left: "0", height: "2px", width: "0%",
    background: U.PAL.red, opacity: "0.85", zIndex: "80", pointerEvents: "none",
    transition: "width .12s linear, opacity .4s",
  });
  document.body.appendChild(prog);
  let progTick = false;
  const updProg = () => {
    progTick = false;
    const max = document.documentElement.scrollHeight - window.innerHeight;
    prog.style.width = (max > 0 ? Math.min(100, window.scrollY / max * 100) : 0) + "%";
  };
  window.addEventListener("scroll", () => {
    if (!progTick) { progTick = true; requestAnimationFrame(updProg); }
  }, { passive: true });
  updProg();

  // ── 当前窗口大数字入场 count-up ──
  const nowGrid = document.getElementById("now-grid");
  if (nowGrid) {
    const numRe = /^([^\d]*)([\d,]+(?:\.\d+)?)(?:(–|-)([\d,]+(?:\.\d+)?))?(.*)$/;
    const fmtNum = (raw, v) => {
      const dec = (raw.split(".")[1] || "").length;
      const s = v.toLocaleString("en-US", { minimumFractionDigits: dec, maximumFractionDigits: dec });
      return raw.includes(",") ? s : s.replace(/,/g, "");
    };
    const numObs = new IntersectionObserver((ents, ob) => {
      ents.forEach(en => {
        if (!en.isIntersecting) return;
        ob.disconnect();
        nowGrid.querySelectorAll(".n-v").forEach((el, i) => {
          const small = el.querySelector("small");
          const smallHtml = small ? small.outerHTML : "";
          const base = el.textContent.replace(small ? small.textContent : "", "");
          const m = base.match(numRe);
          if (!m) return;
          const [, pre, n1s, sep, n2s, suf] = m;
          const n1 = parseFloat(n1s.replace(/,/g, ""));
          const n2 = n2s ? parseFloat(n2s.replace(/,/g, "")) : null;
          if (!isFinite(n1)) return;
          setTimeout(() => U.countUp(el, {
            dur: 1250, html: true,
            fmt: t => pre + fmtNum(n1s, n1 * t) + (n2 != null ? sep + fmtNum(n2s, n2 * t) : "") + suf + smallHtml,
          }), i * 90);
        });
      });
    }, { threshold: 0.3 });
    numObs.observe(nowGrid);
  }
})();

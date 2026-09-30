// Генератор токенов «Ваш Финдир» v2.2 → tokens.json (DTCG) + vf-theme.css (Tailwind v4).
// Источник: raw.json, извлечённый из Design System.dc.html. Руками выходные файлы не правим.
import fs from 'node:fs';

const d = JSON.parse(fs.readFileSync(new URL('./raw.json', import.meta.url)));
const VERSION = d.VERSION;
const px = (n) => (n === 0 ? '0' : `${n}px`);
const esc = (k) => String(k).replace('.', '\\.');

// Фокус и ошибка поля — через семантические переменные, чтобы в .dark
// кольцо само становилось crimson 400 / crimson 900 (focus-inverse из раздела 10).
const SHADOW = {
  sm: d.SH.sm,
  md: d.SH.md,
  lg: d.SH.lg,
  focus: '0 0 0 2px var(--surface), 0 0 0 4px var(--accent)',
  'focus-inverse': d.SH['focus-inverse'],
  'focus-field': 'inset 0 0 0 1px var(--accent), 0 0 0 4px var(--focus-ring)',
  'error-field': 'inset 0 0 0 1px var(--error)',
};
const WEIGHTS = { normal: 400, medium: 500, semibold: 600, bold: 700, extrabold: 800 };
const FAMILY = {
  Manrope: 'var(--font-display)',
  Inter: 'var(--font-sans)',
  'ui-monospace': 'var(--font-mono)',
};
const ASPECT = { video: '16 / 9', photo: '3 / 2', portrait: '4 / 5', screenshot: '16 / 10' };
const ANIM = { spin: 'spin 800ms linear infinite', skeleton: 'vf-skeleton 1500ms ease-in-out infinite' };

// ---------- tokens.json (формат W3C Design Tokens, тёмная тема — в $extensions) ----------
const tok = (type, value, description, dark) => {
  const t = { $type: type, $value: value };
  if (description) t.$description = description;
  if (dark !== undefined && dark !== value) t.$extensions = { 'ru.vashfindir.dark': dark };
  return t;
};

// Ширины, высоты и отступы (SZ): имена классов берём из файла дизайн-системы, не придумываем.
// Ячейка класса бывает составной: «top-sticky · scroll-mt-sticky (< 1024)» → два класса, примечание отбрасываем.
// max-w-* и w-* с уникальным именем → --container-<имя> (Tailwind сам строит max-w-/min-w-/w-).
// min-w-*, max-h-*, h-* и имена, общие для двух классов (max-w-toc / min-w-toc), пространство
// --container-* выразить не может (min-w-toc получил бы 280, max-h-* его не читает) → @utility.
// top-* и scroll-mt-* читают шкалу --spacing: sticky-offset (≥ 1024) и sticky-offset-compact (< 1024)
// сводятся к одной переменной --spacing-sticky (по умолчанию compact, с lg — основное значение).
const classesOf = (cell) => cell.split('·').map((c) => c.replace(/\(.*?\)/g, '').trim()).filter(Boolean);
const SZ = d.SZ.flatMap(([name, size, cell, use]) => classesOf(cell).map((cls) => {
  const m = cls.match(/^(max-w|min-w|w|max-h|h|top|scroll-mt)-(.+)$/);
  if (!m) throw new Error(`SZ «${name}»: класс «${cls}» не разобран`);
  return { name, size, cls, use, prop: m[1], key: m[2] };
}));
const CSS_PROP = { 'max-w': 'max-width', 'min-w': 'min-width', w: 'width', 'max-h': 'max-height', h: 'height' };
const STICKY = SZ.filter((row) => row.prop === 'top' || row.prop === 'scroll-mt');
const SIZING = SZ.filter((row) => !STICKY.includes(row));
const isShared = (row) => SIZING.some((other) => other !== row && other.key === row.key);
const SZ_THEME = SIZING.filter((row) => (row.prop === 'max-w' || row.prop === 'w') && !isShared(row));
const SZ_UTILITY = SIZING.filter((row) => !SZ_THEME.includes(row));

const stickyNames = [...new Set(STICKY.map((row) => row.name))];
const stickyKeys = [...new Set(STICKY.map((row) => row.key))];
if (stickyNames.length !== 2 || stickyKeys.length !== 1 || !stickyNames.some((n) => n.endsWith('-compact'))) {
  throw new Error('SZ: ожидаются sticky-offset и sticky-offset-compact с общими классами top-/scroll-mt-');
}
const STICKY_KEY = stickyKeys[0];
const STICKY_COMPACT = STICKY.find((row) => row.name.endsWith('-compact')).size;
const STICKY_WIDE = STICKY.find((row) => !row.name.endsWith('-compact')).size;

// Алиасы старых имён до v2.4 (changelog 2.3/5, 2.3/6): max-w-text → measure, max-w-page и max-w-article → container.
const ALIASES = { text: 'measure', page: 'container', article: 'container' };
const sizeOf = (name) => {
  const row = SZ.find((r) => r.name === name);
  if (!row) throw new Error(`SZ: нет «${name}» для алиаса`);
  return row.size;
};
const json = {
  $description: `Ваш Финдир — токены дизайн-системы v${VERSION}. Сгенерировано из раздела 29, не править вручную.`,
  color: {
    ink: Object.fromEntries(Object.entries(d.ink).map(([k, v]) => [k, tok('color', v)])),
    crimson: Object.fromEntries(Object.entries(d.crimson).map(([k, v]) => [k, tok('color', v)])),
    'success-400': tok('color', '#4FB883', 'success на ink'),
    'error-400': tok('color', '#E98A6B', 'error на ink'),
    chart: Object.fromEntries(Object.entries(d.CHART).map(([k, v]) => [k, tok('color', v)])),
    semantic: Object.fromEntries(d.SEM.map(([k, l, dk, use]) => [k, tok('color', l, use, dk)])),
  },
  spacing: Object.fromEntries(d.SP.map(([k, v, use]) => [k, tok('dimension', px(v), use)])),
  radius: Object.fromEntries(d.radii.map((r) => [r.token, tok('dimension', r.css, r.use)])),
  borderWidth: { DEFAULT: tok('dimension', '1px'), 2: tok('dimension', '2px', 'фокус и ошибка поля, чекбокс, радио, OTP, зона загрузки') },
  shadow: Object.fromEntries(Object.entries(SHADOW).map(([k, v]) => [k, tok('shadow', v, d.SHU[k])])),
  typography: Object.fromEntries(d.ROLES.map(([k, name, fam, w, fs, lh, ls, use]) => [k, {
    $type: 'typography',
    $value: { fontFamily: fam, fontSize: px(fs), lineHeight: px(lh), letterSpacing: ls.startsWith('-') ? '-0.02em' : '0', fontWeight: w.split(' · ').map(Number) },
    $description: `${name} — ${use}`,
  }])),
  controlHeight: Object.fromEntries(d.CH.map(([k, v, use]) => [k, tok('dimension', px(v), use)])),
  iconSize: Object.fromEntries(d.IC.map(([k, v, use]) => [k, tok('dimension', px(v), use)])),
  duration: Object.fromEntries(d.durations.map((x) => [x.token, tok('duration', x.ms.replace(' ', ''), x.use)])),
  easing: Object.fromEntries(d.EA.map(([k, v, use]) => [k, tok('cubicBezier', v, use)])),
  breakpoint: { md: tok('dimension', '768px'), lg: tok('dimension', '1024px'), xl: tok('dimension', '1440px') },
  container: Object.fromEntries(d.SZ.map(([name, size, cls, use]) => [name, tok('dimension', px(size), `${cls} — ${use}`)])),
};
fs.writeFileSync(new URL('../tokens.json', import.meta.url), JSON.stringify(json, null, 2) + '\n');

// ---------- vf-theme.css (Tailwind v4) ----------
const L = [];
const w = (s = '') => L.push(s);
w(`/* Ваш Финдир · дизайн-система v${VERSION} · тема для Tailwind CSS v4`);
w(`   Сгенерировано из tokens.json — не редактировать вручную.`);
w(`   Подключение: @import "tailwindcss"; @import "./vf-theme.css";  */`);
w();
w(`@custom-variant dark (&:where(.dark, .dark *));`);
w();
w(`/* 1. Семантические переменные: светлая тема и .dark (на <html> или на любом блоке на ink) */`);
w(`:root {`);
d.SEM.forEach(([k, l]) => w(`  --${k}: ${l};`));
w(`}`);
w(`.dark {`);
d.SEM.filter(([, l, dk]) => l !== dk).forEach(([k, , dk]) => w(`  --${k}: ${dk};`));
w(`}`);
w();
w(`/* 2. Тема. Каждое пространство имён сбрасывается: стандартные red-500, p-7, rounded-2xl, shadow-xl и т. п. не существуют. */`);
w(`@theme {`);
w(`  --*: initial;`);
w();
w(`  /* Значения по умолчанию, которые сбросил --*: initial */`);
w(`  --default-transition-duration: 120ms;`);
w(`  --default-transition-timing-function: cubic-bezier(0.2, 0, 0, 1);`);
w(`  --default-font-family: var(--font-sans);`);
w(`  --default-mono-font-family: var(--font-mono);`);
w();
w(`  /* Примитивы — только внутри компонентов; в разметке страниц используйте семантику */`);
w(`  --color-white: #FFFFFF;`);
Object.entries(d.ink).forEach(([k, v]) => w(`  --color-ink-${k}: ${v};`));
Object.entries(d.crimson).forEach(([k, v]) => w(`  --color-crimson-${k}: ${v};`));
w(`  --color-success-400: #4FB883;`);
w(`  --color-error-400: #E98A6B;`);
Object.entries(d.CHART).forEach(([k, v]) => w(`  --color-chart-${k}: ${v};`));
w();
w(`  /* Отступы: только шкала раздела 07 (без --spacing базы, поэтому p-7, gap-9 не собираются) */`);
d.SP.forEach(([k, v]) => w(`  --spacing-${esc(k)}: ${px(v)};`));
w(`  --spacing-0: 0px;`);
w(`  --spacing-px: 1px;`);
w(`  /* Высоты контролов и размеры иконок: h-control-lg, size-icon-md */`);
d.CH.forEach(([k, v]) => w(`  --spacing-${k}: ${px(v)};`));
d.IC.forEach(([k, v]) => w(`  --spacing-${k}: ${px(v)};`));
w();
w(`  --radius-none: 0;`);
d.radii.filter((r) => r.token !== 'none').forEach((r) => w(`  --radius-${r.token}: ${r.css};`));
w();
Object.entries(SHADOW).forEach(([k, v]) => w(`  --shadow-${k}: ${v};`));
w();
w(`  --font-sans: "Inter Variable", Inter, system-ui, sans-serif;`);
w(`  --font-display: "Manrope Variable", Manrope, "Inter Variable", Inter, system-ui, sans-serif;`);
w(`  --font-mono: ui-monospace, SFMono-Regular, Menlo, monospace;`);
Object.entries(WEIGHTS).forEach(([k, v]) => w(`  --font-weight-${k}: ${v};`));
w(`  --tracking-tight: -0.02em;`);
w(`  --tracking-normal: 0;`);
w();
w(`  /* Роли T-шкалы: text-t1 задаёт размер, интерлиньяж, трекинг и базовый вес */`);
d.ROLES.forEach(([k, , , wt, fs, lh, ls]) => {
  w(`  --text-${k}: ${px(fs)};`);
  w(`  --text-${k}--line-height: ${px(lh)};`);
  w(`  --text-${k}--letter-spacing: ${ls.startsWith('-') ? '-0.02em' : '0'};`);
  w(`  --text-${k}--font-weight: ${wt.split(' · ')[0]};`);
});
w();
w(`  --breakpoint-md: 768px;`);
w(`  --breakpoint-lg: 1024px;`);
w(`  --breakpoint-xl: 1440px;`);
SZ_THEME.forEach((row) => w(`  --container-${row.key}: ${px(row.size)};`));
w(`  /* Алиасы старых имён до v2.4: в шаблонах использовать новые */`);
Object.entries(ALIASES).forEach(([old, current]) => w(`  --container-${old}: ${px(sizeOf(current))};`));
w(`  /* Липкий отступ: шапка + 24. По умолчанию < 1024, с lg -- основное значение, переопределение в @layer base */`);
w(`  --spacing-${STICKY_KEY}: ${px(STICKY_COMPACT)};`);
w();
d.EA.forEach(([k, v]) => w(`  --ease-${k}: ${v};`));
Object.entries(ASPECT).forEach(([k, v]) => w(`  --aspect-${k}: ${v};`));
Object.entries(ANIM).forEach(([k, v]) => w(`  --animate-${k}: ${v};`));
w(`}`);
w();
w(`/* 3. Семантические цвета как утилиты: bg-surface, text-fg, border-border-strong, bg-accent-fill … */`);
w(`@theme inline {`);
d.SEM.forEach(([k]) => w(`  --color-${k}: var(--${k});`));
w(`}`);
w();
w(`/* 4. Полные роли одним классом: семейство + размер + вес + трекинг + tnum. Предпочтительный способ. */`);
d.ROLES.forEach(([k, name, fam, wt]) => {
  const num = /num|t8|code/.test(k);
  w(`@utility type-${k} {`);
  w(`  font-family: ${FAMILY[fam]};`);
  w(`  font-size: var(--text-${k});`);
  w(`  line-height: var(--text-${k}--line-height);`);
  w(`  letter-spacing: var(--text-${k}--letter-spacing);`);
  w(`  font-weight: var(--text-${k}--font-weight);`);
  if (num) w(`  font-variant-numeric: tabular-nums;`);
  w(`}`);
});
w();
w(`/* 5. Длительности раздела 20: duration-fast и т. д. */`);
d.durations.forEach((x) => w(`@utility duration-${x.token} { transition-duration: ${x.ms.replace(' ', '')}; }`));
w();
w(`/* 5а. Ширины и ограничения раздела 29, которых нет в пространстве --container-* */`);
SZ_UTILITY.forEach((row) => w(`@utility ${row.cls} { ${CSS_PROP[row.prop]}: ${px(row.size)}; }`));
w();
w(`@keyframes vf-skeleton { 50% { opacity: 0.6; } }`);
w(`@keyframes spin { to { transform: rotate(360deg); } }`);
w();
w(`/* 6. База */`);
w(`@layer base {`);
w(`  *, ::before, ::after, ::backdrop { border-color: var(--border); }  /* v4 по умолчанию currentColor */`);
w(`  html { font-family: var(--font-sans); font-variant-numeric: tabular-nums; }`);
w(`  body { background: var(--surface); color: var(--fg); -webkit-font-smoothing: antialiased; }`);
w(`  a { color: var(--accent); }`);
w(`  @media (min-width: 1024px) { :root { --spacing-${STICKY_KEY}: ${px(STICKY_WIDE)}; } }`);
w(`  :focus-visible { outline: none; }  /* кольцо рисуют компоненты: shadow-focus / shadow-focus-field */`);
w(`  @media (prefers-reduced-motion: reduce) {`);
w(`    *, *::before, *::after {`);
w(`      animation-duration: 1ms !important;`);
w(`      transition-duration: 120ms !important;`);
w(`      transition-property: opacity, color, background-color, border-color !important;`);
w(`    }`);
w(`  }`);
w(`}`);
fs.writeFileSync(new URL('../vf-theme.css', import.meta.url), L.join('\n') + '\n');
console.log('ok', L.length, 'lines');

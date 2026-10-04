#!/usr/bin/env node
// Проверка Twig/HTML-шаблонов на соответствие дизайн-системе «Ваш Финдир».
// ESLint не читает Twig, поэтому шаблоны проверяет этот скрипт (Node 20+, без зависимостей).
//
//   node check-templates.mjs <собранный.css> <папка или файл> [ещё…]
//   пример: node check-templates.mjs site/public/assets/website/app.css site/templates site/assets/scripts/website site/src/Publication/Adapter
//
// Ошибка, если в class="…" встречается:
//   • класс, которого нет в собранном CSS (p-7, bg-red-500, text-sm — тема их не генерирует);
//   • произвольное значение, duration-N, border-N кроме 2, ring/outline — Tailwind их собирает, но системе они чужие.
import fs from 'node:fs';
import path from 'node:path';

const [cssPath, ...dirs] = process.argv.slice(2);
if (!cssPath || !dirs.length) {
  console.error('Использование: node check-templates.mjs <собранный.css> <папка> [папка…]');
  process.exit(2);
}
const css = fs.readFileSync(cssPath, 'utf8');
const EXT = /\.(twig|html|jsx|tsx|php|js)$/;
// В PHP и JS классы лежат в обычных строках, поэтому там ловим только узнаваемые
// классы стандартной темы Tailwind, которых в теме «Ваш Финдир» нет.
const DEFAULT_TW = new RegExp('^(.*:)?(-?)(' + [
  '(bg|text|border|ring|outline|fill|stroke|from|to|via|decoration|divide|placeholder|accent|caret|shadow)-(slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|black)(-\\d{2,3})?(/\\d+)?',
  'text-(xs|sm|base|lg|[2-9]?xl)',
  'leading-(\\d+|none|tight|snug|normal|relaxed|loose)',
  'font-(thin|extralight|light|black)',
  'tracking-(tighter|wide|wider|widest)',
  'rounded(-[trbl]{1,2})?(-(2xl|3xl|4xl))?',
  'max-w-([2-7]?xl|lg|md|sm|xs|prose|screen-\\w+)',
  'shadow(-(xs|2xs|xl|2xl|inner))?',
  'sm:.*|2xl:.*',
].join('|') + ')$');
// Классы, которые задаёт не Tailwind (JS-хуки, сторонние виджеты). Дополняйте осознанно.
const IGNORE = [/^js-/, /^vf-/, /^ym-/, /^group$/, /^peer$/];

const RESTRICT = [
  [/\[.*\]/, 'произвольное значение — возьмите токен из раздела 29'],
  [/^(.*:)?duration-\d+$/, 'длительность — duration-instant / fast / base / slow / deliberate'],
  [/^(.*:)?border(-[xytrblse])?-(?!0$|2$)\d+$/, 'толщина обводки — только border или border-2'],
  [/^(.*:)?(ring|ring-offset|outline)(-\d+)?$/, 'фокус — shadow-focus / shadow-focus-field / shadow-focus-inverse'],
];

const esc = (c) => c.replace(/([^a-zA-Z0-9_-])/g, '\\$1');
const inCss = (c) => new RegExp('\\.' + esc(c).replace(/[\\^$.*+?()[\]{}|]/g, '\\$&') + '(?![\\w-])').test(css);

const files = dirs.flatMap((d) => (fs.statSync(d).isFile() ? [d] : fs.readdirSync(d, { recursive: true })
  .map((f) => path.join(d, String(f))))).filter((f) => EXT.test(f));

let problems = 0;
const report = (seen, file, i, c, msg) => {
  const key = `${i}:${c}`;
  if (seen.has(key)) return;
  seen.add(key); problems++;
  console.log(`${file}:${i + 1}  ${c}  — ${msg}`);
};
for (const file of files) {
  const lines = fs.readFileSync(file, 'utf8').split('\n');
  const markup = /\.(twig|html|jsx|tsx)$/.test(file);
  const seen = new Set();
  lines.forEach((line, i) => {
    // 1. Любые строки в кавычках (PHP-массивы, JS, тернарники внутри {{ … }}):
    //    ловим классы стандартной темы и запрещённые значения (кроме [..] — это бывают CSS-селекторы).
    for (const m of line.matchAll(/(["'])((?:(?!\1).)*)\1/g)) {
      for (const c of m[2].split(/\s+/).filter(Boolean)) {
        // `variant: 'outline'` names a button variant, not a Tailwind class.
        if (markup && c === 'outline' && /\bvariant\s*:\s*$/.test(line.slice(0, m.index))) continue;
        const rule = RESTRICT.slice(1).find(([r]) => r.test(c));
        if (rule) report(seen, file, i, c, rule[1]);
        else if (DEFAULT_TW.test(c)) report(seen, file, i, c, 'класс стандартной темы Tailwind — замените по MIGRATION.md');
      }
    }
    if (!markup) return;
    // 2. Атрибут class="…" в разметке: каждый класс должен быть в собранном CSS.
    for (const m of line.matchAll(/\bclass(?:Name)?\s*=\s*(["'])(.*?)\1/g)) {
      const raw = m[2].replace(/\{\{.*?\}\}|\{%.*?%\}|\$\{.*?\}/g, ' ');
      for (const c of raw.split(/\s+/).filter(Boolean)) {
        if (IGNORE.some((r) => r.test(c))) continue;
        const rule = RESTRICT.find(([r]) => r.test(c));
        const msg = rule ? rule[1] : inCss(c) ? null : 'класса нет в теме';
        if (msg) report(seen, file, i, c, msg);
      }
    }
  });
}
console.log(problems ? `\n✖ ${problems} нарушений в ${files.length} файлах` : `✓ ${files.length} файлов, нарушений нет`);
process.exit(problems ? 1 : 0);

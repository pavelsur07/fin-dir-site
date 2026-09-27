#!/usr/bin/env node
// Проверка Twig/HTML-шаблонов на соответствие дизайн-системе «Ваш Финдир».
// ESLint не читает Twig, поэтому шаблоны проверяет этот скрипт (Node 20+, без зависимостей).
//
//   node check-templates.mjs <собранный.css> <папка-шаблонов> [ещё папки…]
//   пример: node check-templates.mjs public/build/app.css templates assets/react
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
const EXT = /\.(twig|html|jsx|tsx)$/;
// Классы, которые задаёт не Tailwind (JS-хуки, сторонние виджеты). Дополняйте осознанно.
const IGNORE = [/^js-/, /^vf-/, /^group$/, /^peer$/];

const RESTRICT = [
  [/\[.*\]/, 'произвольное значение — возьмите токен из раздела 29'],
  [/^(.*:)?duration-\d+$/, 'длительность — duration-instant / fast / base / slow / deliberate'],
  [/^(.*:)?border(-[xytrblse])?-(?!0$|2$)\d+$/, 'толщина обводки — только border или border-2'],
  [/^(.*:)?(ring|ring-offset|outline)(-\d+)?$/, 'фокус — shadow-focus / shadow-focus-field / shadow-focus-inverse'],
];

const esc = (c) => c.replace(/([^a-zA-Z0-9_-])/g, '\\$1');
const inCss = (c) => css.includes('.' + esc(c) + ' ') || css.includes('.' + esc(c) + ':') ||
  css.includes('.' + esc(c) + ',') || css.includes('.' + esc(c) + '{') || css.includes('.' + esc(c) + ')');

const files = dirs.flatMap((d) => fs.readdirSync(d, { recursive: true })
  .map((f) => path.join(d, String(f))).filter((f) => EXT.test(f)));

let problems = 0;
for (const file of files) {
  const lines = fs.readFileSync(file, 'utf8').split('\n');
  lines.forEach((line, i) => {
    for (const m of line.matchAll(/\bclass(?:Name)?\s*=\s*(["'])(.*?)\1/g)) {
      // Выкидываем Twig/JSX-вставки: {{ … }}, {% … %}, ${ … }
      const raw = m[2].replace(/\{\{.*?\}\}|\{%.*?%\}|\$\{.*?\}/g, ' ');
      for (const c of raw.split(/\s+/).filter(Boolean)) {
        if (IGNORE.some((r) => r.test(c))) continue;
        const rule = RESTRICT.find(([r]) => r.test(c));
        const msg = rule ? rule[1] : inCss(c) ? null : 'класса нет в теме';
        if (msg) { problems++; console.log(`${file}:${i + 1}  ${c}  — ${msg}`); }
      }
    }
  });
}
console.log(problems ? `\n✖ ${problems} нарушений в ${files.length} файлах` : `✓ ${files.length} файлов, нарушений нет`);
process.exit(problems ? 1 : 0);

// node tools/extract.cjs "Design System.dc.html"  → tools/raw.json
// Достаёт массивы токенов из скрипта файла дизайн-системы (Claude Design).
const fs = require('fs'), path = require('path');
const s = fs.readFileSync(process.argv[2], 'utf8');
let js = s.match(/<script type="text\/x-dc"[^>]*>([\s\S]*?)<\/script>\s*<\/body>/)[1];
// Данные отдаёт последний `    return { … }` с отступом 4 — главный return renderVals(); якорь не зависит от порядка ключей.
const RET = /^    return \{ /gm;
let last; for (let m; (m = RET.exec(js));) last = m;
js = js.slice(0, last.index) + '    globalThis.__T={ink,crimson,SEM,SP,radii,SH,SHU,ROLES,CH,IC,durations,EA,CHART,breakpoints,SZ};\n' + js.slice(last.index);
// Скрипт файла создаёт ref через React.createRef(); в Node React нет.
globalThis.React = { createRef: () => ({ current: null }) };
new Function('DCLogic', js + '\nconst c=new Component();c.state=c.state||{};c.renderVals();')(class { constructor() { this.props = {}; } setState() {} });
// Версия — бейдж «vX.Y» в шапке файла; SZ — ширины и ограничения (имя, px, класс, применение).
globalThis.__T.VERSION = s.match(/>v(\d+\.\d+)</)[1];
fs.writeFileSync(path.join(__dirname, 'raw.json'), JSON.stringify(globalThis.__T, null, 1));
console.log('raw.json ← ' + process.argv[2]);

// node tools/extract.cjs "Design System.dc.html"  → tools/raw.json
// Достаёт массивы токенов из скрипта файла дизайн-системы (Claude Design).
const fs = require('fs'), path = require('path');
const s = fs.readFileSync(process.argv[2], 'utf8');
let js = s.match(/<script type="text\/x-dc"[^>]*>([\s\S]*?)<\/script>\s*<\/body>/)[1];
js = js.replace(/    return \{ ((?:\w+, )*toc,)/, '    globalThis.__T={ink,crimson,SEM,SP,radii,SH,SHU,ROLES,CH,IC,durations,EA,CHART,breakpoints,SZ};\n    return { $1');
new Function('DCLogic', js + '\nconst c=new Component();c.state=c.state||{};c.renderVals();')(class { constructor() { this.props = {}; } setState() {} });
// Версия — бейдж «vX.Y» в шапке файла; SZ — ширины и ограничения (имя, px, класс, применение).
globalThis.__T.VERSION = s.match(/>v(\d+\.\d+)</)[1];
fs.writeFileSync(path.join(__dirname, 'raw.json'), JSON.stringify(globalThis.__T, null, 1));
console.log('raw.json ← ' + process.argv[2]);

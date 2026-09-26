// Ваш Финдир · сборка токенов: tokens.json → CSS / TS / JSON для Figma.
// npx style-dictionary build --config tokens/style-dictionary.config.mjs
export default {
  source: ['tokens/tokens.json'],
  platforms: {
    css: {
      transformGroup: 'css',
      prefix: 'vf',
      buildPath: 'dist/',
      files: [{ destination: 'tokens.css', format: 'css/variables', options: { outputReferences: true } }]
    },
    ts: {
      transformGroup: 'js',
      buildPath: 'dist/',
      files: [
        { destination: 'tokens.js', format: 'javascript/es6' },
        { destination: 'tokens.d.ts', format: 'typescript/es6-declarations' }
      ]
    },
    figma: {
      transformGroup: 'js',
      buildPath: 'dist/',
      files: [{ destination: 'tokens.figma.json', format: 'json/nested' }]
    }
  }
};
// Тёмная тема и компонентные токены держатся в tokens/themes/*.json и собираются
// в селектор [data-theme="dark"] кастомным форматом (см. README, раздел «Темы»).

// Ваш Финдир · ESLint (flat config) — запрет инлайн-стилей и «сырых» значений в JSX/TS.
import react from 'eslint-plugin-react';

const HEX = '/^#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/';
const RGB = '/^(rgb|rgba|hsl|hsla)\\(/';

export default [
  {
    files: ['src/**/*.{js,jsx,ts,tsx}'],
    ignores: ['packages/ui/src/tokens/**'],
    plugins: { react },
    rules: {
      // 1. Никаких style={{…}} ни на DOM-элементах, ни на компонентах
      'react/forbid-dom-props': ['error', { forbid: [{ propName: 'style', message: 'Инлайн-стили запрещены. Используйте компонент или класс из @vashfindir/ui.' }] }],
      'react/forbid-component-props': ['error', { forbid: [
        { propName: 'style', message: 'Инлайн-стили запрещены.' },
        { propName: 'className', allowedFor: ['Stack', 'Cluster', 'Grid', 'Container'], message: 'Внешний вид меняется через props (variant, size, tone), а не через className.' }
      ] }],
      // 2. Цвета строкой в коде — только в пакете токенов
      'no-restricted-syntax': ['error',
        { selector: `Literal[value=${HEX}]`, message: 'Hex-цвет в коде. Используйте токен var(--vf-color-*).' },
        { selector: `Literal[value=${RGB}]`, message: 'rgb()/hsl() в коде. Используйте токен var(--vf-color-*).' },
        { selector: `TemplateElement[value.raw=${HEX}]`, message: 'Hex-цвет в коде. Используйте токен.' }
      ],
      // 3. Импорт компонентов только из пакета, не из внутренних путей
      'no-restricted-imports': ['error', { patterns: [{ group: ['@vashfindir/ui/src/*', '@vashfindir/ui/dist/*'], message: 'Импортируйте из @vashfindir/ui' }] }]
    }
  },
  {
    // Исключение: графики получают цвет из JS (canvas/SVG) — только через getToken()
    files: ['src/**/charts/**'],
    rules: { 'react/forbid-dom-props': 'off' }
  }
];

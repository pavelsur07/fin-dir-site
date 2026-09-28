// eslint.config.js — страж дизайн-системы «Ваш Финдир».
// npm i -D eslint eslint-plugin-better-tailwindcss
// Для JSX/TSX добавьте свой парсер (typescript-eslint / @eslint/js) как обычно.
import betterTailwind from 'eslint-plugin-better-tailwindcss';

export default [
  {
    files: ['**/*.{js,jsx,ts,tsx}'],
    plugins: { 'better-tailwindcss': betterTailwind },
    settings: {
      // путь к CSS, где лежат @import "tailwindcss" и @import "./vf-theme.css"
      'better-tailwindcss': { entryPoint: 'assets/styles/app.css' },
    },
    rules: {
      // Класс, которого нет в теме (p-7, bg-red-500, text-sm), — ошибка.
      'better-tailwindcss/no-unknown-classes': ['error', { ignore: ['^vf-'] }],
      'better-tailwindcss/no-conflicting-classes': 'error',
      'better-tailwindcss/no-duplicate-classes': 'error',
      // То, что Tailwind v4 собирает всегда, но системе не принадлежит.
      'better-tailwindcss/no-restricted-classes': ['error', {
        restrict: [
          { pattern: '^.*\\[.*\\].*$', message: 'Произвольное значение «$0» запрещено: возьмите токен из раздела 29 или попросите дизайнера добавить его.' },
          { pattern: '^(.*:)?duration-\\d+$', message: '«$0»: используйте duration-instant / fast / base / slow / deliberate.' },
          { pattern: '^(.*:)?border(-[xytrblse])?-(?!0$|2$)\\d+$', message: '«$0»: толщина обводки только 1 (border) или 2 (border-2).' },
          { pattern: '^(.*:)?(ring|ring-offset|outline)(-\\d+)?$', message: '«$0»: фокус рисуется shadow-focus / shadow-focus-field / shadow-focus-inverse.' },
        ],
      }],
    },
  },
];

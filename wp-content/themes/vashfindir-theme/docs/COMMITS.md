# Правила коммитов

## Формат
Используем префиксы:
- `feat:` новая функциональность
- `fix:` исправление
- `refactor:` рефакторинг без изменения поведения
- `docs:` документация
- `chore:` инфраструктура/служебное

## Атомарность
1 коммит = 1 проверяемое изменение.

Примеры хороших атомарных коммитов:
- `chore: add atomic design folders`
- `docs: add theme rules and structure`
- `refactor: move inline css to assets`

Плохие:
- `update theme`
- `many changes`

# Архитектурные паттерны проекта

## 1. Назначение

Документ определяет:

- слои и их ответственность;
- путь обработки read- и write-запросов;
- разрешённые и запрещённые архитектурные паттерны;
- правила работы с Entity, Query, DTO и Doctrine;
- единый подход к пагинации через Pagerfanta.

Главный принцип: применять самое простое решение, которое сохраняет бизнес-
инварианты, границы модулей и понятность сопровождения.

Полный CQRS, чистая архитектура и микросервисы не являются архитектурной целью
проекта. Они вводятся только при подтверждённой необходимости.

## 2. Базовая структура модуля

```text
ModuleName/
├── Controller/
├── Entity/
├── Repository/
├── Query/
├── Service/
├── DTO/
├── Form/
├── Validator/
├── Security/
├── Message/
├── MessageHandler/
└── Exception/
```

Создавать только реально используемые каталоги. Для небольшого модуля
достаточно `Entity`, `Repository`, `Service` и `Controller`.

Если один сценарий чтения состоит из нескольких классов, их можно сгруппировать:

```text
Billing/
└── Query/
    └── InvoiceList/
        ├── InvoiceListQuery.php
        ├── InvoiceListRequest.php
        ├── InvoiceListCriteria.php
        └── InvoiceListItem.php
```

## 3. Слои и ответственность

| Компонент | Ответственность | Не должен делать |
|---|---|---|
| `Controller` | HTTP-маршрут, получение входа, проверка доступа, вызов одного сценария, Response | Doctrine-запросы, бизнес-логика, `flush()`, создание Pagerfanta |
| `Request DTO` / `Form` | Преобразование и валидация внешнего ввода | Загружать Entity, выполнять бизнес-сценарий |
| `Query` | Чтение, фильтры, company scope, сортировка, проекция и пагинация | Менять состояние, выполнять `persist()` или `flush()` |
| `Query DTO` / `ViewModel` | Данные строки списка, отчёта или представления | Содержать бизнес-операции и Doctrine lazy associations |
| `Application Service` | Координация одного write-сценария и его транзакционной границы | Подменять бизнес-правила Entity, формировать HTTP-ответ |
| `Entity` | Собственные инварианты, бизнес-поведение и переходы состояния | Зависеть от HTTP, Doctrine-сервисов, внешних API |
| `Value Object` | Валидация и поведение значения | Иметь изменяемое состояние и инфраструктурные зависимости |
| `Domain Service` | Бизнес-правило между несколькими объектами без естественного владельца | Оркестрировать HTTP, БД и внешние интеграции |
| `Repository` | Получение и сохранение Entity для изменения состояния | Формировать UI-списки, содержать бизнес-правила |
| `Adapter` | Внешний API, файл, транспорт или инфраструктурная интеграция | Владеть бизнес-решением и инвариантами |
| `Twig` | Представление уже подготовленных данных | Запрашивать БД, обращаться к lazy associations, считать бизнес-показатели |

## 4. Обработка read-запроса

Путь запроса списка:

```text
GET Request
→ Controller
→ Request DTO / Criteria / PageRequest
→ Query
→ Doctrine QueryBuilder
→ Pagerfanta QueryAdapter
→ DTO-проекции текущей страницы
→ Twig или JsonResponse
```

Последовательность:

1. Symfony вызывает контроллер по маршруту.
2. Внешние параметры преобразуются в типизированный request DTO.
3. Symfony Validator проверяет страницу, размер страницы, фильтры и сортировку.
4. Контроллер проверяет право доступа.
5. Контроллер вызывает один специализированный Query.
6. Query применяет company scope, фильтры и разрешённую сортировку.
7. Query выбирает только нужные поля и формирует DTO-проекцию.
8. Query создаёт Doctrine `QueryAdapter` и `Pagerfanta`.
9. Pagerfanta получает количество результатов и данные текущей страницы.
10. Контроллер передаёт результат в Twig или возвращает API response.

Контроллер не знает:

- структуру таблиц и joins;
- способ подсчёта результатов;
- поля SQL/DQL-сортировки;
- способ создания QueryAdapter и Pagerfanta;
- правила DTO-гидрации.

## 5. Обработка write-запроса

Путь команды изменения:

```text
POST / PUT / PATCH / DELETE Request
→ Controller
→ Request DTO / Form
→ Application Service
→ Repository загружает Entity
→ Entity выполняет бизнес-метод
→ Unit of Work сохраняет изменения
→ RedirectResponse или JsonResponse
```

Последовательность:

1. Контроллер получает и передаёт на валидацию внешний ввод.
2. Контроллер проверяет доступ к сценарию.
3. Application Service загружает нужные Entity через Repository.
4. Entity проверяет собственные инварианты и выполняет бизнес-операцию.
5. Application Service координирует другие разрешённые действия сценария.
6. Изменения сохраняются в одной явно определённой транзакционной границе.
7. Контроллер возвращает HTTP-ответ.

Query, list DTO и Pagerfanta в write-сценарии не участвуют.

## 6. Тонкие контроллеры

Контроллер может:

- объявить маршрут и допустимые HTTP-методы;
- принять типизированный и валидированный вход;
- получить текущего пользователя и контекст компании;
- проверить доступ;
- вызвать один Query или один Application Service;
- выбрать шаблон, redirect или тип HTTP-ответа;
- передать уже подготовленный результат в представление.

Контроллеру запрещено:

- создавать Doctrine `QueryBuilder`;
- обращаться к EntityManager;
- выполнять `persist()`, `remove()` или `flush()`;
- создавать `QueryAdapter` или `Pagerfanta`;
- реализовывать фильтрацию и сортировку;
- выполнять бизнес-расчёты;
- изменять состояние Entity;
- управлять транзакциями;
- загружать данные в цикле;
- преобразовывать Entity в строки списка или API DTO;
- обрабатывать исключения, для которых существует общий exception listener.

Пример read-контроллера:

```php
final class InvoiceListController extends AbstractController
{
    #[Route('/invoices', name: 'invoice_list', methods: ['GET'])]
    public function __invoke(
        InvoiceListRequest $request,
        InvoiceListQuery $query,
    ): Response {
        $this->denyAccessUnlessGranted('INVOICE_LIST');

        return $this->render('billing/invoice/list.html.twig', [
            'pager' => $query->paginate(
                $request->criteria(),
                $request->pageRequest(),
            ),
        ]);
    }
}
```

Пример write-контроллера:

```php
final class InvoiceCancelController extends AbstractController
{
    #[Route('/invoices/{id}/cancel', name: 'invoice_cancel', methods: ['POST'])]
    public function __invoke(
        string $id,
        InvoiceCancelRequest $request,
        InvoiceCanceller $canceller,
    ): Response {
        $this->denyAccessUnlessGranted('INVOICE_CANCEL', $id);

        $canceller->cancel($id, $request->reason());

        return $this->redirectToRoute('invoice_list');
    }
}
```

## 7. Entity с бизнес-поведением

Entity отвечает за собственное корректное состояние и правила, относящиеся
непосредственно к ней.

В Entity размещаются:

- собственные инварианты;
- допустимые переходы между статусами;
- расчёты на основании собственного состояния;
- изменение собственных полей;
- правила добавления и удаления дочерних объектов;
- защита от создания недопустимого состояния;
- создание доменных событий, если они применяются в проекте.

Предпочтительны бизнес-методы:

```text
approve()
cancel()
markAsPaid()
changeAmount()
addOperation()
closePeriod()
```

Не использовать публичные универсальные сеттеры для обхода бизнес-правил:

```text
setStatus()
setPaid()
setBalance()
setClosed()
```

Пример:

```php
final class Invoice
{
    public function cancel(
        CancellationReason $reason,
        \DateTimeImmutable $cancelledAt,
    ): void {
        if (!$this->status->canBeCancelled()) {
            throw new InvoiceCannotBeCancelled($this->id);
        }

        $this->status = InvoiceStatus::CANCELLED;
        $this->cancellationReason = $reason;
        $this->cancelledAt = $cancelledAt;
    }
}
```

В Entity запрещены:

- EntityManager, Repository и `flush()`;
- HTTP Request, Session и Security Context;
- вызовы внешних API;
- отправка email и уведомлений;
- чтение файлов и environment;
- формирование списков, отчётов и DTO представления;
- изменение Entity другого бизнес-модуля;
- оркестрация независимых агрегатов.

Граница:

> Entity решает, можно ли изменить её состояние. Application Service решает,
> какую Entity загрузить, когда вызвать операцию и что сделать после неё.

## 8. Application Service и Domain Service

Application Service представляет один конкретный пользовательский или системный
сценарий:

```text
InvoiceCreator
InvoiceCanceller
PaymentRegistrar
CompanyMemberInviter
```

Он может:

- загружать Entity через Repository;
- вызывать их бизнес-методы;
- определять транзакционную границу;
- координировать адаптеры и другие разрешённые зависимости;
- публиковать сообщение или событие после успешной операции.

Domain Service допустим, когда бизнес-правило:

- использует несколько Entity или Value Object;
- не принадлежит естественно одной Entity;
- не является инфраструктурной координацией.

Не создавать `Manager`, `Helper`, `Utils` или универсальный `CommonService`.

Command + Handler добавляются, если:

- сценарий вызывается из нескольких интерфейсов;
- нужна очередь;
- необходимы идемпотентность или повторное выполнение;
- сценарий стал достаточно сложным, чтобы явный контракт улучшал поддержку.

Для простой синхронной операции отдельные Command и Handler не обязательны.

## 9. Чтение данных: Query + Projection

Специализированные Query обязательны для:

- списков и таблиц;
- поиска и autocomplete;
- dashboard и информеров;
- отчётов;
- экспортов;
- API, возвращающего коллекции.

Query должен:

- выбирать только необходимые поля;
- возвращать readonly DTO, ViewModel, scalar result или массив проекций;
- применять обязательный company scope;
- использовать типизированные критерии;
- проверять сортировку по белому списку;
- обеспечивать стабильный порядок;
- учитывать риск N+1 и дублей от joins;
- не изменять состояние системы.

Запрещено:

- использовать `findAll()` для пользовательских списков;
- возвращать `Entity[]` из Query;
- передавать коллекции Entity в Twig или API;
- сериализовать Doctrine Entity;
- загружать все колонки, если нужны отдельные поля;
- использовать Doctrine partial objects вместо DTO;
- обращаться к lazy associations внутри цикла или Twig;
- выполнять `persist()`, `remove()` или `flush()` внутри Query.

Полноценная Doctrine Entity используется для бизнес-поведения и изменения
состояния. Для чтения списков, отчётов и экспортов используется Query с
проекцией в DTO/ViewModel.

## 10. Пагинация через Pagerfanta

Для пагинации используется пакет:

```text
babdev/pagerfanta-bundle
```

Query возвращает:

```text
Pagerfanta<InvoiceListItem>
```

Запрещено возвращать для пользовательского списка:

```text
Pagerfanta<Invoice>
```

Query отвечает за создание Doctrine `QueryAdapter` и Pagerfanta. Controller не
должен знать о деталях адаптера.

Концептуальный пример:

```php
/**
 * @return PagerfantaInterface<InvoiceListItem>
 */
public function paginate(
    InvoiceListCriteria $criteria,
    PageRequest $page,
): PagerfantaInterface {
    $queryBuilder = $this->entityManager
        ->createQueryBuilder()
        ->select(sprintf(
            'NEW %s(i.id, i.number, i.status, i.totalAmount, i.createdAt)',
            InvoiceListItem::class,
        ))
        ->from(Invoice::class, 'i')
        ->andWhere('i.company = :company')
        ->setParameter('company', $criteria->companyId());

    $this->applyFilters($queryBuilder, $criteria);
    $this->applySorting($queryBuilder, $criteria);

    return Pagerfanta::createForCurrentPageWithMaxPerPage(
        new QueryAdapter(
            $queryBuilder,
            fetchJoinCollection: false,
        ),
        $page->number(),
        $page->size(),
    );
}
```

Конкретный способ DTO-гидрации выбирается с учётом установленной версии
Doctrine.

### 10.1. Входные параметры

- `page` по умолчанию равен `1`;
- `perPage` имеет серверное значение по умолчанию;
- максимальный `perPage` ограничен, обычно не более `100`;
- нулевые, отрицательные и нечисловые значения отклоняются;
- пользователь не может передавать произвольное SQL/DQL-поле сортировки.

Пример белого списка:

```php
private const SORT_FIELDS = [
    'createdAt' => 'i.createdAt',
    'number' => 'i.number',
    'status' => 'i.status',
    'amount' => 'i.totalAmount',
];
```

### 10.2. Стабильная сортировка

Пагинируемый запрос всегда имеет однозначный порядок:

```sql
ORDER BY i.createdAt DESC, i.id DESC
```

Уникальное поле добавляется вторым критерием, чтобы строки с одинаковым первым
значением не перемещались между страницами.

### 10.3. Ограничение выборки

Запрещено:

```php
$items = $repository->findAll();
$pager = new Pagerfanta(new ArrayAdapter($items));
```

Такой код загружает весь результат из БД и имитирует пагинацию в памяти.

`ArrayAdapter` допустим только для уже существующего малого массива, который
не был получен полной выборкой из БД.

### 10.4. Doctrine joins

- избегать `to-many fetch join` в пагинируемых списках;
- не обращаться к lazy associations внутри Twig;
- при joins проверять отсутствие дублей;
- отдельно тестировать корректность count;
- осознанно задавать `fetchJoinCollection` и `useOutputWalkers`;
- для тяжёлого отчёта допускается специализированный DBAL Query с отдельным
  count-запросом.

### 10.5. Несуществующая страница

Страница за пределами допустимого диапазона возвращает HTTP `404`, а не
последнюю существующую страницу.

Преобразование исключения Pagerfanta в `404` настраивается централизованно.
Контроллеры не должны повторять одинаковый `try/catch`.

### 10.6. Twig

Twig получает DTO-проекции:

```twig
{% for item in pager.currentPageResults %}
    {# item — InvoiceListItem, не Entity #}
{% endfor %}

{% if pager.haveToPaginate %}
    {{ pagerfanta(pager, {
        omitFirstPage: true
    }) }}
{% endif %}
```

Фильтры и сортировка должны сохраняться при переходе между страницами.

## 11. Repository

Repository используется для получения и сохранения Entity в write-сценариях.

Допустимы методы с предметным смыслом:

```text
get(InvoiceId $id)
findPendingForCompany(CompanyId $companyId)
save(Invoice $invoice)
```

Repository не должен:

- содержать бизнес-правила;
- изменять несколько агрегатов;
- формировать ViewModel пользовательского списка;
- возвращать неограниченную коллекцию;
- использоваться напрямую из контроллера.

Для сложного read-сценария использовать отдельный Query, даже если технически
он применяет тот же EntityManager.

## 12. Разрешённые паттерны

- Entity с бизнес-поведением;
- Value Object;
- Application Service;
- Domain Service при естественном отсутствии одной Entity-владельца;
- Query + Projection DTO;
- Repository для Entity;
- Factory для сложного создания валидного объекта;
- Adapter для внешнего API и инфраструктуры;
- Strategy для реально взаимозаменяемых алгоритмов;
- Domain Event для реакции другого модуля на произошедшее событие;
- Test Data Builder для подготовки тестовых данных.

## 13. Условно разрешённые паттерны

Применять только при подтверждённой необходимости, указанной в плане:

- Command Bus;
- Query Bus;
- полный CQRS;
- Specification;
- Decorator;
- State;
- асинхронные Domain Events;
- отдельный интерфейс для каждого сервиса;
- отдельный Python/Go-сервис;
- отдельный React SPA;
- локальные слои `Domain/Application/Infrastructure/UI`.

Перед применением ответить:

1. Какую конкретную проблему решает паттерн?
2. Почему существующего простого решения недостаточно?
3. Уменьшает ли он связанность или только добавляет классы?
4. Как он будет тестироваться?
5. Можно ли отказаться от него без изменения бизнес-поведения?

Если польза не подтверждена, использовать более простое решение.

## 14. Запрещённые паттерны и решения

Запрещено создавать:

- `CommonService`, `Helper`, `Utils`;
- универсальные `Manager`-классы;
- универсальный CRUD-сервис для всех Entity;
- базовый Repository с бизнес-логикой;
- Service Locator;
- статические бизнес-сервисы;
- бизнес-логику в Controller, Form, Twig или Repository;
- анемичные Entity, управляемые универсальными публичными сеттерами;
- прямое изменение Entity другого модуля;
- циклические зависимости модулей;
- полные Entity для списков и отчётов;
- события для сокрытия обязательной синхронной операции;
- интерфейсы без архитектурной границы или практической причины;
- абстракции исключительно «на будущее»;
- дублирование бизнес-логики в Symfony и отдельном сервисе.

## 15. Тестирование архитектурных границ

| Уровень | Что проверять |
|---|---|
| `Unit` | Инварианты Entity, Value Object, PageRequest, Criteria и белый список сортировок |
| `Integration` | Query на реальной тестовой БД, company scope, фильтры, DTO, count и отсутствие дублей |
| `Functional` | HTTP-маршрут, валидацию, security, пагинацию и `404` отсутствующей страницы |
| `E2E` | Переходы по страницам и сохранение фильтров в критичных интерфейсах |

Для интеграционных и функциональных тестов применять Test Data Builder согласно
`AGENTS.md`.

Для нового списка минимум проверить:

- изоляцию данных компаний;
- допустимые и недопустимые фильтры;
- белый список сортировки;
- стабильный порядок;
- первую, среднюю и последнюю страницу;
- страницу за пределами диапазона;
- корректный count при joins;
- отсутствие N+1 на критичном списке.

## 16. Краткая схема выбора

```text
Нужно показать данные?
→ Query + DTO/ViewModel
→ Pagerfanta, если это список

Нужно изменить состояние?
→ Application Service
→ Repository загружает Entity
→ Entity выполняет бизнес-метод

Правило относится только к одной Entity?
→ Entity

Правило относится к нескольким объектам и не имеет естественного владельца?
→ Domain Service

Нужно обратиться к внешней системе?
→ Adapter
```

## 17. Главная архитектурная граница

> Controller управляет HTTP. Query управляет чтением и пагинацией. Entity
> управляет собственным состоянием. Application Service управляет бизнес-
> сценарием. Adapter управляет внешней инфраструктурой.

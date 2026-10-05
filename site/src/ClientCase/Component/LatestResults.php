<?php

declare(strict_types=1);

namespace App\ClientCase\Component;

use App\ClientCase\Query\PublicCaseList\CaseListItem;
use App\ClientCase\Query\PublicCaseList\PublicCaseListQuery;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Секция «Результаты клиентов в цифрах»: свежие опубликованные кейсы из модуля. Подгружает данные сама,
 * поэтому страницам не нужен ни контроллер с запросом, ни переменная в шаблоне:
 * <twig:ClientCase:LatestResults tone="muted" />. Нет кейсов -- секции нет совсем.
 */
#[AsTwigComponent]
final class LatestResults
{
    /** Сколько кейсов показать. */
    public int $limit = 3;

    /** Фон секции: 'surface' | 'muted'. Выбирается по соседним секциям страницы, чтобы фоны чередовались. */
    public string $tone = 'surface';

    /** Якорь секции; id заголовка -- «<id>-title». Нужен уникальным, если секция есть на странице один раз. */
    public string $id = 'latest-case-results';

    public string $title = 'Результаты клиентов в цифрах';

    public string $allLabel = 'Все кейсы';

    public function __construct(private readonly PublicCaseListQuery $cases)
    {
    }

    /**
     * Три самых свежих опубликованных кейса (по дате публикации). Список не отдаёт признак «главный»,
     * поэтому «избранного» отбора нет. Пустой slug ничего не исключает.
     *
     * @return list<CaseListItem>
     */
    public function getCases(): array
    {
        return $this->cases->latestExcept('', max(0, $this->limit));
    }
}

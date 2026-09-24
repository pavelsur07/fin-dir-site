<?php

declare(strict_types=1);

namespace App\Lead\Adapter;

/**
 * Уведомление о новом обращении: только номер и ссылка на карточку в админке.
 * Ни данных формы, ни страницы, ни UTM -- всё остаётся в админке (Telegram --
 * трансграничная передача, 152-ФЗ; согласие обещает не передавать данные третьим лицам).
 */
final readonly class LeadNotification
{
    public function __construct(
        public int $leadId,
    ) {
    }

    public function text(string $adminUrl): string
    {
        // HTML401: апостроф -> &#039;. Именованный &apos; (HTML5) Telegram не понимает
        // и отвечает 400 "can't parse entities".
        $url = htmlspecialchars($adminUrl, \ENT_QUOTES | \ENT_HTML401);

        return \sprintf("<b>Новое обращение №%d</b>\n\n<a href=\"%s\">Открыть в админке</a>", $this->leadId, $url);
    }
}

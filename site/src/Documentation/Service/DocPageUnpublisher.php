<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\Exception\DocConflict;
use App\Documentation\Repository\DocPageRepository;
use Psr\Clock\ClockInterface;

final class DocPageUnpublisher
{
    public function __construct(private readonly DocPageRepository $pages, private readonly DocWriteTransaction $transaction, private readonly ClockInterface $clock)
    {
    }

    public function unpublish(int $id, int $version): void
    {
        $this->transaction->run(function () use ($id, $version): void {
            $page = $this->pages->get($id);
            if ($page->version() !== $version) {
                throw new DocConflict('Форма устарела. Обновите страницу.');
            }
            if ([] !== $this->pages->publishedIncoming($id)) {
                throw new DocConflict('Сначала уберите входящие ссылки из опубликованных страниц.');
            }
            $page->unpublish($this->clock->now());
        });
    }
}

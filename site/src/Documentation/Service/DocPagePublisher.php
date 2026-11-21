<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\Exception\DocConflict;
use App\Documentation\Repository\DocPageRepository;
use Psr\Clock\ClockInterface;

final class DocPagePublisher
{
    public function __construct(private readonly DocPageRepository $pages, private readonly DocWriteTransaction $transaction, private readonly DocPageContentEditor $content, private readonly ClockInterface $clock)
    {
    }

    public function publish(int $id, int $version): void
    {
        $this->transaction->run(function () use ($id, $version): void {
            $page = $this->pages->get($id);
            if ($page->version() !== $version) {
                throw new DocConflict('Форма устарела. Обновите страницу.');
            }
            $this->content->validatePublishedLinks($page);
            $this->content->validateImages($page);
            $page->publish($this->clock->now());
        });
    }
}

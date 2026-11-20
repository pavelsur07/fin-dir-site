<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\DTO\DocPageInput;
use App\Documentation\Entity\DocPage;
use App\Documentation\Repository\DocPageRepository;
use Psr\Clock\ClockInterface;

final class DocPageCreator
{
    public function __construct(private readonly DocPageRepository $pages, private readonly DocWriteTransaction $transaction, private readonly DocPageContentEditor $content, private readonly ClockInterface $clock)
    {
    }

    public function create(DocPageInput $input): int
    {
        $page = $this->transaction->run(function () use ($input): DocPage {
            $page = new DocPage($input->slug, $this->clock->now());
            $this->content->apply($page, $input);
            $this->pages->save($page);

            return $page;
        });

        return $page->id();
    }
}

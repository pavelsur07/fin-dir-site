<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\DTO\DocDraftWriteInput;
use App\Documentation\DTO\DocDraftWriteResult;
use App\Documentation\Entity\DocPage;
use App\Documentation\Entity\DocToolReceipt;
use App\Documentation\Repository\DocPageRepository;
use App\Documentation\Repository\DocToolReceiptRepository;
use Psr\Clock\ClockInterface;

final class DocDraftCreator
{
    public function __construct(private readonly DocPageRepository $pages, private readonly DocToolReceiptRepository $receipts, private readonly DocWriteTransaction $transaction, private readonly DocPageContentEditor $content, private readonly ClockInterface $clock)
    {
    }

    public function create(DocDraftWriteInput $input, string $actor): DocDraftWriteResult
    {
        [$page, $replayed] = $this->transaction->run(function () use ($input, $actor): array {
            $hash = $input->hash('docs_create_draft');
            $receipt = $this->receipts->find($actor, $input->requestKey);
            if (null !== $receipt) {
                return [$receipt->replay('docs_create_draft', $hash), true];
            }
            $page = new DocPage($input->page->slug, $this->clock->now());
            $this->content->apply($page, $input->page);
            $this->pages->save($page);
            $this->receipts->save(new DocToolReceipt($actor, $input->requestKey, 'docs_create_draft', $hash, $page, $this->clock->now()));

            return [$page, false];
        });

        return new DocDraftWriteResult($page instanceof DocPage ? $page->id() : $page, $input->requestKey, $replayed);
    }
}

<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\DTO\DocDraftWriteInput;
use App\Documentation\DTO\DocDraftWriteResult;
use App\Documentation\Entity\DocToolReceipt;
use App\Documentation\Exception\DocToolFailure;
use App\Documentation\Repository\DocPageRepository;
use App\Documentation\Repository\DocToolReceiptRepository;
use Psr\Clock\ClockInterface;

final class DocDraftEditor
{
    public function __construct(private readonly DocPageRepository $pages, private readonly DocToolReceiptRepository $receipts, private readonly DocWriteTransaction $transaction, private readonly DocPageContentEditor $content, private readonly ClockInterface $clock)
    {
    }

    public function edit(int $id, int $expectedVersion, DocDraftWriteInput $input, string $actor): DocDraftWriteResult
    {
        return $this->transaction->run(function () use ($id, $expectedVersion, $input, $actor): DocDraftWriteResult {
            $hash = $input->hash('docs_update_draft', $id, $expectedVersion);
            $receipt = $this->receipts->find($actor, $input->requestKey);
            if (null !== $receipt) {
                return new DocDraftWriteResult($receipt->replay('docs_update_draft', $hash), $input->requestKey, true);
            }
            $page = $this->pages->get($id);
            if ($page->isPublished()) {
                throw new DocToolFailure('PAGE_NOT_DRAFT', 'Редактировать можно только черновик.');
            }
            if ($page->version() !== $expectedVersion) {
                throw new DocToolFailure('VERSION_CONFLICT', 'Страница изменена. Прочитайте её актуальную версию.');
            }
            $this->content->apply($page, $input->page);
            $this->receipts->save(new DocToolReceipt($actor, $input->requestKey, 'docs_update_draft', $hash, $page, $this->clock->now()));

            return new DocDraftWriteResult($id, $input->requestKey, false);
        });
    }
}

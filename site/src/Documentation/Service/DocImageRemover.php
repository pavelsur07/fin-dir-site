<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\Exception\DocConflict;
use App\Documentation\Repository\DocImageRepository;

final class DocImageRemover
{
    public function __construct(private readonly DocImageRepository $images, private readonly DocWriteTransaction $transaction)
    {
    }

    public function remove(int $pageId, string $id): void
    {
        $this->transaction->run(function () use ($pageId, $id): void {
            if (!$this->images->removeUnused($pageId, $id)) {
                throw new DocConflict('Снимок используется в сохранённом тексте или отсутствует.');
            }
        });
    }
}

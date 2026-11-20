<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\DTO\DocPageInput;
use App\Documentation\Exception\DocConflict;
use App\Documentation\Repository\DocPageRepository;

final class DocPageEditor
{
    public function __construct(private readonly DocPageRepository $pages, private readonly DocWriteTransaction $transaction, private readonly DocPageContentEditor $content)
    {
    }

    public function edit(int $id, DocPageInput $input): void
    {
        $this->transaction->run(function () use ($id, $input): void {
            $page = $this->pages->get($id);
            if ($page->version() !== $input->version) {
                throw new DocConflict('Форма устарела. Обновите страницу.');
            }
            $this->content->apply($page, $input);
        });
    }
}

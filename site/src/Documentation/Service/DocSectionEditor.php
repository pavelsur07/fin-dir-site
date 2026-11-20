<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\DTO\DocSectionInput;
use App\Documentation\Exception\DocConflict;
use App\Documentation\Repository\DocSectionRepository;
use Psr\Clock\ClockInterface;

final class DocSectionEditor
{
    public function __construct(private readonly DocSectionRepository $sections, private readonly DocWriteTransaction $transaction, private readonly ClockInterface $clock)
    {
    }

    public function edit(int $id, DocSectionInput $input): void
    {
        $this->transaction->run(function () use ($id, $input): void {
            $section = $this->sections->get($id);
            if ($section->version() !== $input->version) {
                throw new DocConflict('Форма устарела. Обновите страницу.');
            }
            if ($section->slug() !== $input->slug) {
                throw new DocConflict('Адрес раздела менять нельзя.');
            }
            $section->edit($input->title, $input->description, $input->position, $this->clock->now());
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\DTO\DocSectionInput;
use App\Documentation\Entity\DocSection;
use App\Documentation\Repository\DocSectionRepository;
use Psr\Clock\ClockInterface;

final class DocSectionCreator
{
    public function __construct(private readonly DocSectionRepository $sections, private readonly DocWriteTransaction $transaction, private readonly ClockInterface $clock)
    {
    }

    public function create(DocSectionInput $input): int
    {
        $section = $this->transaction->run(function () use ($input): DocSection {
            $section = new DocSection($input->title, $input->slug, $input->description, $input->position, $this->clock->now());
            $this->sections->save($section);

            return $section;
        });

        return $section->id();
    }
}

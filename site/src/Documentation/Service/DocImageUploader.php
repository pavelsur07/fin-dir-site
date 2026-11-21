<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\Adapter\DocImageNormalizer;
use App\Documentation\DTO\DocImageUpload;
use App\Documentation\Entity\DocImage;
use App\Documentation\Exception\DocConflict;
use App\Documentation\Repository\DocImageRepository;
use App\Documentation\Repository\DocPageRepository;
use App\Documentation\ValueObject\DocImageId;
use Psr\Clock\ClockInterface;

final class DocImageUploader
{
    public function __construct(
        private readonly DocImageNormalizer $normalizer,
        private readonly DocImageRepository $images,
        private readonly DocPageRepository $pages,
        private readonly DocWriteTransaction $transaction,
        private readonly ClockInterface $clock,
    ) {
    }

    public function upload(int $pageId, string $bytes, bool $draftOnly = false): DocImageUpload
    {
        $image = $this->normalizer->normalize($bytes);
        $sha256 = hash('sha256', $image['contents']);

        return $this->transaction->run(function () use ($pageId, $draftOnly, $image, $sha256): DocImageUpload {
            $page = $this->pages->get($pageId);
            if ($draftOnly && $page->isPublished()) {
                throw new DocConflict('Локальный импорт разрешён только для черновиков.');
            }

            $id = $this->images->duplicateId($pageId, $sha256);
            if (null === $id) {
                $id = DocImageId::create();
                $this->images->save(new DocImage($id, $page, $sha256, $image['width'], $image['height'], $image['contents'], $this->clock->now()));
            }

            return new DocImageUpload($id, $image['width'], $image['height']);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\Adapter\DocMarkdownProcessor;
use App\Documentation\DTO\DocPageInput;
use App\Documentation\Entity\DocPage;
use App\Documentation\Entity\DocPageLink;
use App\Documentation\Exception\DocConflict;
use App\Documentation\Exception\DocNotFound;
use App\Documentation\Repository\DocImageRepository;
use App\Documentation\Repository\DocPageRepository;
use App\Documentation\Repository\DocSectionRepository;
use App\Documentation\ValueObject\DocLinkType;
use Psr\Clock\ClockInterface;

final class DocPageContentEditor
{
    public function __construct(private readonly DocPageRepository $pages, private readonly DocSectionRepository $sections, private readonly DocMarkdownProcessor $markdown, private readonly ClockInterface $clock, private readonly DocPublicationRules $rules, private readonly DocImageRepository $images)
    {
    }

    public function apply(DocPage $page, DocPageInput $input): void
    {
        $analysis = $this->markdown->analyze($input->body);
        $this->checkImages($page, $analysis->images);
        $desired = [];
        $existingTargets = [];
        foreach ($page->links() as $existing) {
            foreach ($existing->sourceUrls() as $url) {
                $existingTargets[$url] = $existing->target();
            }
        }
        foreach ($analysis->links as $i => $link) {
            if (null === $link['id'] && null === $link['slug']) {
                $this->checkAnchor($input->body, $link['fragment']);
                continue;
            }
            try {
                $target = null !== $link['id'] ? $this->pages->get($link['id']) : ($existingTargets[$link['url']] ?? $this->pages->bySlug((string) $link['slug']));
            } catch (DocNotFound $e) {
                throw new DocConflict('Выбранная страница не найдена. Выберите ссылку заново.', 0, $e);
            }
            $this->checkAnchor($target === $page ? $input->body : $target->body(), $link['fragment']);

            $desired[] = ['target' => $target, 'type' => DocLinkType::INLINE, 'fragment' => $link['fragment'], 'position' => $i, 'urls' => [$link['url']]];
        }
        $relatedIds = $input->relatedIds;
        if ('' !== trim($input->relatedLinks)) {
            $refs = $this->markdown->analyze($input->relatedLinks)->links;
            if ([] === $refs) {
                throw new DocConflict('Используйте ссылки из окна выбора страниц в «Читайте также».');
            }
            foreach ($refs as $ref) {
                if (null === $ref['id'] || '' !== $ref['fragment']) {
                    throw new DocConflict('В «Читайте также» выберите страницы целиком.');
                }
                $relatedIds[] = $ref['id'];
            }
        }
        foreach ($relatedIds as $i => $id) {
            try {
                $relatedTarget = $this->pages->get($id);
            } catch (DocNotFound $e) {
                throw new DocConflict('Страница в «Читайте также» не найдена. Выберите ссылку заново.', 0, $e);
            }
            $desired[] = ['target' => $relatedTarget, 'type' => DocLinkType::RELATED, 'fragment' => '', 'position' => $i, 'urls' => []];
        }
        $body = $input->body;
        foreach ($this->incoming($page) as $incoming) {
            $this->checkAnchor($body, $incoming->fragment());
        }
        $page->edit($this->sections->get($input->sectionId), $input->title, $input->slug, $input->description, $body, $analysis->plainText, $input->position, $input->metaTitle, $input->metaDescription, $this->clock->now());
        $page->replaceLinks($desired);
        if ($page->isPersisted()) {
            $this->images->replaceReferences($page->id(), array_values(array_unique(array_column($analysis->images, 'id'))));
        }
        if ($page->isPublished()) {
            $this->validatePublishedLinks($page);
        }
    }

    /** @return list<DocPageLink> */
    private function incoming(DocPage $page): array
    {
        try {
            return $this->pages->publishedIncoming($page->id());
        } catch (\LogicException) {
            return [];
        }
    }

    public function validatePublishedLinks(DocPage $page): void
    {
        foreach ($page->links() as $link) {
            if (!$this->rules->targetIsPublishable($page->id(), $link->target()->id(), $link->target()->isPublished() ? 'published' : 'draft')) {
                throw new DocConflict('Сначала опубликуйте все страницы, на которые ссылается материал.');
            }
            $this->checkAnchor($link->target()->body(), $link->fragment());
        }
    }

    public function validateImages(DocPage $page): void
    {
        $this->checkImages($page, $this->markdown->analyze($page->body())->images);
    }

    /** @param list<array{id:string,alt:string}> $references */
    private function checkImages(DocPage $page, array $references): void
    {
        if ([] === $references) {
            return;
        }
        $issues = $this->rules->inspectImages($references, $page->isPersisted() ? $this->images->idsForPage($page->id()) : []);
        if ([] !== $issues) {
            throw new DocConflict($issues[0]['message']);
        }
    }

    private function checkAnchor(string $body, string $fragment): void
    {
        if (!$this->rules->hasAnchor($body, $fragment)) {
            throw new DocConflict('Заголовок, на который ведёт ссылка, отсутствует: '.$fragment);
        }
    }
}

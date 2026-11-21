<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\Adapter\DocMarkdownProcessor;
use App\Documentation\DTO\DocPublicationCandidate;
use App\Documentation\DTO\DocValidationReport;
use App\Documentation\ValueObject\DocFields;
use Psr\Clock\ClockInterface;

final class DocPublicationRules
{
    public function __construct(private readonly DocMarkdownProcessor $markdown, private readonly ClockInterface $clock)
    {
    }

    public function targetIsPublishable(int $sourceId, int $targetId, string $status): bool
    {
        return $sourceId === $targetId || 'published' === $status;
    }

    public function hasAnchor(string $body, string $fragment): bool
    {
        return '' === $fragment || in_array($fragment, array_column($this->markdown->analyze($body)->headings, 'id'), true);
    }

    public function inspect(DocPublicationCandidate $candidate): DocValidationReport
    {
        $issues = [];
        foreach (DocFields::publicationContentIssues($candidate->description, $candidate->body) as $code) {
            $issues[] = ['code' => $code, 'message' => 'Для публикации заполните текст и описание.'];
        }
        $byId = $byUrl = $bySlug = [];
        foreach ($candidate->targets as $target) {
            $byId[$target['id']] = $target;
            if (isset($target['slug'])) {
                $bySlug[$target['slug']] = $target;
            }
            foreach ($target['urls'] ?? [] as $url) {
                $byUrl[$url] = $target;
            }
        }
        foreach ($this->markdown->analyze($candidate->body)->links as $link) {
            if (null === $link['id'] && null === $link['slug'] && !$this->hasAnchor($candidate->body, $link['fragment'])) {
                $issues[] = ['code' => 'MISSING_ANCHOR', 'message' => 'Локальный заголовок отсутствует.'];
            }
            if (null !== $link['id'] || null !== $link['slug']) {
                $target = null !== $link['id'] ? ($byId[$link['id']] ?? null) : ($byUrl[$link['url']] ?? $bySlug[$link['slug']] ?? null);
                if (null === $target) {
                    $issues[] = ['code' => 'MISSING_TARGET', 'message' => 'Внутренняя цель ссылки отсутствует или недоступна.'];
                } elseif (!$this->hasAnchor($candidate->id === $target['id'] ? $candidate->body : $target['body'], $link['fragment'])) {
                    $issues[] = ['code' => 'MISSING_ANCHOR', 'message' => 'Заголовок цели отсутствует.', 'targetId' => $target['id']];
                }
            }
        }
        foreach ($candidate->targets as $target) {
            if (!$this->targetIsPublishable($candidate->id, $target['id'], $target['status'])) {
                $issues[] = ['code' => 'TARGET_NOT_PUBLISHED', 'message' => 'Целевая страница не опубликована.', 'targetId' => $target['id']];
            }
            if (!$this->hasAnchor($candidate->id === $target['id'] ? $candidate->body : $target['body'], $target['fragment'])) {
                $issues[] = ['code' => 'MISSING_ANCHOR', 'message' => 'Заголовок цели отсутствует.', 'targetId' => $target['id']];
            }
        }
        foreach ($candidate->incomingAnchors as $incoming) {
            if (!$this->hasAnchor($candidate->body, $incoming['fragment'])) {
                $issues[] = ['code' => 'INCOMING_ANCHOR_BROKEN', 'message' => 'Входящая ссылка ведёт на отсутствующий заголовок.'];
            }
        }

        return new DocValidationReport($candidate->id, $candidate->version, $issues, $this->clock->now());
    }
}

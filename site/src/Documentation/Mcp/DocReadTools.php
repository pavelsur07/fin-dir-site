<?php

declare(strict_types=1);

namespace App\Documentation\Mcp;

use App\Documentation\Adapter\DocMarkdownProcessor;
use App\Documentation\DTO\DocMcpSearchInput;
use App\Documentation\DTO\DocPublicationCandidate;
use App\Documentation\Exception\DocToolFailure;
use App\Documentation\Query\EditorialDocMcpQuery;
use App\Documentation\Query\PublishedDocMcpQuery;
use App\Documentation\Service\DocPublicationRules;
use Pagerfanta\PagerfantaInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DocReadTools
{
    public function __construct(private readonly PublishedDocMcpQuery $published, private readonly EditorialDocMcpQuery $editorial, private readonly DocMarkdownProcessor $markdown, private readonly DocPublicationRules $rules, private readonly ValidatorInterface $validator)
    {
    }

    /** @param array<string,mixed> $args
     * @return array<string,mixed> */
    public function call(string $tool, array $args, DocMcpAccess $access): array
    {
        $query = $access->canReadDrafts() ? $this->editorial : $this->published;
        if ('docs_list_sections' === $tool) {
            return $this->paginate($query->sections($args['page'] ?? 1));
        }
        if ('docs_search' === $tool) {
            if ('draft' === ($args['status'] ?? null)) {
                $access->assertAllowed('docs:drafts:read');
            }
            $input = new DocMcpSearchInput(trim($args['q'] ?? ''), $args['page'] ?? 1, $args['sectionId'] ?? null, $args['status'] ?? null);
            if (count($this->validator->validate($input)) > 0) {
                throw new DocToolFailure('VALIDATION_FAILED', 'Проверьте параметры поиска.');
            }
            $data = $this->paginate($query->search($input));
            $data['items'] = array_map(fn (array $row): array => $this->urls($row, $access), $data['items']);

            return $data;
        }
        if ('docs_get_links' === $tool) {
            return $this->paginate($query->links($args['id'], $args['direction'] ?? 'outgoing', $args['page'] ?? 1));
        }
        $page = $query->page($args['id'])->data;
        if ('docs_get_page' === $tool) {
            $page['headings'] = $this->markdown->analyze($page['body'])->headings;

            return $this->urls($page, $access);
        }
        $targets = $query->targets($args['id']);
        if ('docs_preview_page' === $tool) {
            $rendered = $this->markdown->renderTargets($page['body'], $targets);

            return ['pageId' => $page['id'], 'version' => $page['version'], 'html' => $rendered->html, 'toc' => $rendered->toc, 'adminPreviewUrl' => $access->canReadDrafts() ? 'https://vashfindir.ru/admin/docs/pages/'.$page['id'].'/preview' : null];
        }
        if ('docs_validate_page' !== $tool) {
            throw new DocToolFailure('FORBIDDEN', 'Неизвестный сценарий чтения.');
        }
        $candidate = new DocPublicationCandidate($page['id'], $page['version'], $page['description'], $page['body'], array_map(static fn (array $target): array => ['id' => (int) $target['target_id'], 'status' => (string) $target['status'], 'body' => (string) $target['body'], 'fragment' => (string) $target['fragment'], 'slug' => (string) $target['slug'], 'urls' => is_string($target['source_urls']) ? json_decode($target['source_urls'], true, 512, JSON_THROW_ON_ERROR) : $target['source_urls']], $targets), $query->incomingAnchors($page['id']));

        return $this->rules->inspect($candidate)->toArray();
    }

    /** @param PagerfantaInterface<array<string,mixed>> $pager
     * @return array<string,mixed> */
    private function paginate(PagerfantaInterface $pager): array
    {
        $items = [];
        foreach ($pager->getCurrentPageResults() as $row) {
            foreach (['id', 'section_id', 'source_id', 'target_id', 'position'] as $key) {
                if (isset($row[$key])) {
                    $row[$key] = (int) $row[$key];
                }
            }
            $names = ['section_id' => 'sectionId', 'source_id' => 'sourceId', 'target_id' => 'targetId', 'source_title' => 'sourceTitle', 'target_title' => 'targetTitle'];
            foreach ($names as $from => $to) {
                if (array_key_exists($from, $row)) {
                    $row[$to] = $row[$from];
                    unset($row[$from]);
                }
            }
            $items[] = $row;
        }

        return ['items' => $items, 'page' => $pager->getCurrentPage(), 'pageSize' => 20, 'total' => $pager->getNbResults(), 'hasNext' => $pager->hasNextPage()];
    }

    /** @param array<string,mixed> $page
     * @return array<string,mixed> */
    private function urls(array $page, DocMcpAccess $access): array
    {
        $page['publicUrl'] = 'published' === $page['status'] ? 'https://docs.vashfindir.ru/articles/'.$page['slug'] : null;
        if ($access->canReadDrafts()) {
            $page['adminEditUrl'] = 'https://vashfindir.ru/admin/docs/pages/'.$page['id'].'/edit';
        }

        return $page;
    }
}

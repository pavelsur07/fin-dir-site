<?php

declare(strict_types=1);

namespace App\Documentation\Adapter;

use App\Documentation\ValueObject\DocUrl;
use App\Shared\Adapter\MarkdownRenderer;
use App\Shared\Adapter\RenderedArticle;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\StringContainerHelper;
use League\CommonMark\Node\StringContainerInterface;

final class DocMarkdownProcessor
{
    public function __construct(private readonly MarkdownRenderer $markdown, private readonly DocUrl $urls = new DocUrl())
    {
    }

    public function link(string $title, int $id, string $fragment = ''): string
    {
        $title = strtr($title, ['\\' => '\\\\', '[' => '\\[', ']' => '\\]', "\n" => ' ']);

        return '['.$title.'](doc:'.$id.('' === $fragment ? '' : '#'.$fragment).')';
    }

    public function analyze(string $body): DocMarkdownAnalysis
    {
        $document = $this->markdown->convert($body)->getDocument();
        $links = [];
        $headings = [];
        $text = [];
        foreach ($document->iterator() as $node) {
            if ($node instanceof Heading) {
                $id = $node->data->get('attributes/id');
                if (is_string($id)) {
                    $headings[] = ['id' => $id, 'title' => StringContainerHelper::getChildText($node)];
                }
            }
            if ($node instanceof StringContainerInterface) {
                $text[] = $node->getLiteral();
            }
            if (!$node instanceof Link) {
                continue;
            }
            $url = $node->getUrl();
            $parts = parse_url($url);
            if (false === $parts) {
                continue;
            }
            $fragment = rawurldecode($parts['fragment'] ?? '');
            if (str_starts_with($url, '#')) {
                $links[] = ['url' => $url, 'id' => null, 'slug' => null, 'fragment' => $fragment];
                continue;
            }
            if (preg_match('/^doc:([1-9][0-9]*)(?:#.*)?$/D', $url, $m)) {
                $links[] = ['url' => $url, 'id' => (int) $m[1], 'slug' => null, 'fragment' => $fragment];
                continue;
            }
            if (str_starts_with(strtolower($url), 'doc:')) {
                $links[] = ['url' => $url, 'id' => 0, 'slug' => null, 'fragment' => $fragment];
                continue;
            }
            $host = strtolower($parts['host'] ?? '');
            if (('' === $host && !isset($parts['scheme'])) || 'docs.vashfindir.ru' === $host) {
                if (preg_match('~^/articles/([a-z0-9]+(?:-[a-z0-9]+)*)$~D', $parts['path'] ?? '', $m)) {
                    $links[] = ['url' => $url, 'id' => null, 'slug' => $m[1], 'fragment' => $fragment];
                }
            }
        }

        $images = [];
        /** @var list<array{url:string,alt:string}> $references */
        $references = $document->data->get('image_references', []);
        foreach ($references as $image) {
            if (str_starts_with($image['url'], 'doc-image:')) {
                $images[] = [
                    'id' => substr($image['url'], strlen('doc-image:')),
                    'alt' => preg_replace('/\A[\p{Z}\s]+|[\p{Z}\s]+\z/u', '', $image['alt']) ?? '',
                ];
            }
        }

        return new DocMarkdownAnalysis($links, $headings, implode(' ', $text), $images);
    }

    /** @param list<array<string,mixed>> $targets
     * @param array<string,array{url:string,width:int,height:int}> $images */
    public function renderTargets(string $body, array $targets, array $images = []): RenderedArticle
    {
        $map = [];
        foreach ($this->analyze($body)->links as $link) {
            if (null !== $link['id'] || null !== $link['slug']) {
                $map[$link['url']] = '';
            }
        }
        foreach ($targets as $target) {
            $fragment = (string) $target['fragment'];
            $suffix = '' === $fragment ? '' : '#'.$fragment;
            $map['doc:'.$target['target_id'].$suffix] = $this->urls->route('docs_page', (string) $target['slug']).$suffix;
            $originals = $target['source_urls'] ?? [];
            if (is_string($originals)) {
                $originals = json_decode($originals, true, 512, JSON_THROW_ON_ERROR);
            }
            if (is_array($originals)) {
                foreach ($originals as $original) {
                    if (is_string($original)) {
                        $map[$original] = $this->urls->route('docs_page', (string) $target['slug']).$suffix;
                    }
                }
            }
        }

        return $this->render($body, $map, $images);
    }

    /** @param array<string,string> $targets
     * @param array<string,array{url:string,width:int,height:int}> $images */
    public function render(string $body, array $targets, array $images = []): RenderedArticle
    {
        $map = [];
        foreach ($images as $id => $image) {
            $map['doc-image:'.$id] = $image;
        }

        return $this->markdown->renderArticle($body, $targets, $map);
    }
}

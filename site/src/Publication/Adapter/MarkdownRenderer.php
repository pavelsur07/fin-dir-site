<?php

declare(strict_types=1);

namespace App\Publication\Adapter;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\StringContainerHelper;

/**
 * Adapter над league/commonmark. HTML из текста статьи вырезается, небезопасные
 * ссылки (javascript:, data:) отбрасываются -- результат можно выводить через |raw.
 * Картинки не выводятся (загрузки ещё нет, внешние CDN запрещены SITE_RULES §7.4):
 * вместо ![alt](url) остаётся текст alt.
 */
final class MarkdownRenderer
{
    private readonly MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
            // id у заголовков -- якоря для оглавления; значок-ссылку не вставляем.
            'heading_permalink' => [
                'insert' => 'none',
                'apply_id_to_heading' => true,
                'id_prefix' => 'section',
                'min_heading_level' => 2,
                'max_heading_level' => 3,
            ],
            // Внешние ссылки открываются в той же вкладке, но без доступа к window.opener.
            'external_link' => [
                'internal_hosts' => ['vashfindir.ru', 'www.vashfindir.ru'],
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
            // Таблица шире экрана прокручивается внутри обёртки, а не ломает страницу.
            'table' => [
                'wrap' => ['enabled' => true, 'tag' => 'div', 'attributes' => ['class' => 'my-6 overflow-x-auto rounded-lg border border-slate-200']],
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new ExternalLinkExtension());
        // H1 страницы -- заголовок статьи: "# " в тексте становится h2.
        // Приоритет выше HeadingPermalink, чтобы id получили уже понижённые заголовки.
        $environment->addEventListener(DocumentParsedEvent::class, static function (DocumentParsedEvent $event): void {
            foreach ($event->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
                if ($node instanceof Heading && 1 === $node->getLevel()) {
                    $node->setLevel(2);
                }
            }

            $images = [];
            foreach ($event->getDocument()->iterator() as $node) {
                if ($node instanceof Image) {
                    $images[] = $node;
                }
            }
            foreach ($images as $image) {
                // Текст alt остаётся в строке, сама картинка -- нет.
                foreach ($image->children() as $child) {
                    $image->insertBefore($child);
                }
                $image->detach();
            }
        }, 100);

        $this->converter = new MarkdownConverter($environment);
    }

    public function renderArticle(string $markdown): RenderedArticle
    {
        $result = $this->converter->convert($markdown);

        $toc = [];
        foreach ($result->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if ($node instanceof Heading && 2 === $node->getLevel()) {
                $id = $node->data->get('attributes/id', null);
                if (\is_string($id)) {
                    $toc[] = ['id' => $id, 'title' => StringContainerHelper::getChildText($node)];
                }
            }
        }

        return new RenderedArticle($this->addTailwindClasses($result->getContent()), $toc);
    }

    private function addTailwindClasses(string $html): string
    {
        $classes = [
            'h2' => 'mb-4 mt-10 text-3xl font-bold leading-tight',
            'h3' => 'mb-3 mt-8 text-xl font-bold leading-7',
            'p' => 'mb-4',
            'ul' => 'mb-4 list-disc pl-6',
            'ol' => 'mb-4 list-decimal pl-6',
            'li' => 'mb-2',
            'a' => 'text-red-700 underline hover:text-red-800',
            'blockquote' => 'my-6 border-l-4 border-red-700 bg-red-50 p-4',
            'pre' => 'my-6 overflow-x-auto rounded-lg bg-slate-900 p-4 text-sm text-white',
            'code' => 'rounded bg-slate-100 px-1 text-sm',
            'table' => 'w-full border-collapse text-left',
            'th' => 'border-b border-slate-300 p-3 font-semibold',
            'td' => 'border-b border-slate-200 p-3',
            'hr' => 'my-8 border-t border-slate-200',
        ];

        return preg_replace_callback('/<(h2|h3|p|ul|ol|li|a|blockquote|pre|code|table|th|td|hr)(?=[\\s>])([^>]*)>/',
            static function (array $match) use ($classes): string {
                $tag = $match[1];
                $attributes = $match[2];

                return '<'.$tag.' class="'.$classes[$tag].'"'.$attributes.'>';
            },
            $html,
        ) ?? $html;
    }
}

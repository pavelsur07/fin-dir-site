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
                'wrap' => ['enabled' => true, 'tag' => 'div', 'attributes' => ['class' => 'vf-table-wrap']],
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

        return new RenderedArticle($result->getContent(), $toc);
    }
}

<?php

declare(strict_types=1);

namespace App\Publication\Adapter;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Node;
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
    private const string CALLOUT_SUMMARY = 'summary';
    private const string CALLOUT_PRACTICE = 'practice';
    private const string CALLOUT_KEY = 'key';

    // Таблица шире экрана прокручивается внутри обёртки, а не ломает страницу.
    // TODO: ждём токен от дизайнера (мин. ширина таблицы, п.3): min-w-modal-md взят временно.
    private const array TABLE_WRAPPER_ATTRIBUTES = [
        'class' => 'my-6 overflow-x-auto rounded-md border focus-visible:shadow-focus',
        'tabindex' => '0',
        'role' => 'region',
        'aria-label' => 'Таблица',
    ];
    private const string TABLE_CLASS = 'w-full min-w-modal-md border-collapse text-left tabular-nums type-t6';

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
            'table' => [
                'wrap' => ['enabled' => true, 'tag' => 'div', 'attributes' => self::TABLE_WRAPPER_ATTRIBUTES],
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new ExternalLinkExtension());
        // H1 страницы -- заголовок статьи: "# " в тексте становится h2.
        // Приоритет выше HeadingPermalink, чтобы id получили уже понижённые заголовки.
        $environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event): void {
            foreach ($event->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
                if ($node instanceof Heading && 1 === $node->getLevel()) {
                    $node->setLevel(2);
                }
            }

            $this->markCallouts($event->getDocument());
            $this->markFaqQuestions($event->getDocument());

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
            'h2' => 'mb-5 mt-12 scroll-mt-6 type-t2-article text-fg first:mt-0',
            'h3' => 'mb-3 mt-8 type-t3 text-fg',
            'p' => 'mb-5 text-pretty last:mb-0',
            'ul' => 'mb-4 list-disc pl-5 last:mb-0',
            'ol' => 'mb-4 list-decimal pl-6 last:mb-0',
            'li' => 'mb-2 last:mb-0',
            'a' => 'text-accent underline hover:text-accent-fill-hover focus-visible:shadow-focus',
            'blockquote' => 'my-6 rounded-md border-l-2 border-border-strong bg-surface-muted p-6',
            'pre' => 'my-6 overflow-x-auto rounded-lg dark bg-surface-muted p-4 type-t6 text-fg',
            'code' => 'rounded-xs bg-surface-muted px-1 type-t6',
            'table' => self::TABLE_CLASS,
            'th' => 'border-b border-border-strong bg-surface-muted px-4 py-3 type-t6 font-medium text-fg-muted',
            'td' => 'border-b border-border-subtle px-4 py-3 align-top type-t6 text-fg',
            'hr' => 'my-8 border-t border-border-subtle',
        ];
        $calloutClasses = [
            self::CALLOUT_SUMMARY => 'my-6 rounded-md border-l-2 border-accent bg-surface-muted p-6',
            self::CALLOUT_PRACTICE => 'my-6 rounded-md border border-success-border bg-success-bg p-6',
            self::CALLOUT_KEY => 'my-6 rounded-md border-l-2 border-border-strong bg-surface-muted p-6',
        ];

        return preg_replace_callback('/<(h2|h3|p|ul|ol|li|a|blockquote|pre|code|table|th|td|hr)(?=[\\s>])([^>]*)>/',
            static function (array $match) use ($classes, $calloutClasses): string {
                $tag = $match[1];
                $attributes = $match[2];
                $class = $classes[$tag];

                if ('blockquote' === $tag && 1 === preg_match('/data-vf-callout="([a-z]+)"/', $attributes, $callout)) {
                    $class = $calloutClasses[$callout[1]] ?? $class;
                }
                if ('h3' === $tag && str_contains($attributes, 'data-vf-faq="question"')) {
                    $class = 'mb-2 mt-6 border-t border-border-subtle pt-6 type-t3 text-fg';
                }
                if ('th' === $tag) {
                    $attributes .= ' scope="col"';
                }

                return '<'.$tag.' class="'.$class.'"'.$attributes.'>';
            },
            $html,
        ) ?? $html;
    }

    /**
     * Вид врезки задаёт жирная метка в начале цитаты: «Короткий вывод», «Best practice»;
     * остальные цитаты (в том числе «Главный вопрос») -- нейтральная врезка ключевой мысли.
     */
    private function markCallouts(Node $document): void
    {
        foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if (!$node instanceof BlockQuote) {
                continue;
            }

            $label = '';
            $paragraph = $node->firstChild();
            $strong = $paragraph instanceof Paragraph ? $paragraph->firstChild() : null;
            if ($strong instanceof Strong) {
                $label = mb_strtolower(StringContainerHelper::getChildText($strong));
            }

            $kind = match (true) {
                str_starts_with($label, 'короткий вывод') => self::CALLOUT_SUMMARY,
                str_starts_with($label, 'best practice') => self::CALLOUT_PRACTICE,
                default => self::CALLOUT_KEY,
            };
            $node->data->set('attributes/data-vf-callout', $kind);
        }
    }

    /**
     * Раздел «частые вопросы» (h2): каждый h3 внутри -- вопрос, всё до следующего заголовка -- ответ.
     * Метка нужна и стилю, и будущему JSON-LD FAQPage.
     */
    private function markFaqQuestions(Node $document): void
    {
        $inFaq = false;
        foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if (!$node instanceof Heading) {
                continue;
            }
            if (2 === $node->getLevel()) {
                $title = mb_strtolower(StringContainerHelper::getChildText($node));
                $inFaq = str_contains($title, 'частые вопросы') || 'faq' === $title;
            } elseif ($inFaq && 3 === $node->getLevel()) {
                $node->data->set('attributes/data-vf-faq', 'question');
            }
        }
    }
}

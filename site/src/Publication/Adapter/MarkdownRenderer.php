<?php

declare(strict_types=1);

namespace App\Publication\Adapter;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListData;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
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
    // Три вида врезок (раздел 26): «Коротко», «Важно», «Вопрос / Совет».
    private const string CALLOUT_SUMMARY = 'summary';
    private const string CALLOUT_INFO = 'info';
    private const string CALLOUT_NOTE = 'note';

    private const string ICON_INFO = 'info';
    private const string ICON_HELP = 'help';
    private const string ICON_TIP = 'tip';

    // Lucide, те же пути, что в components/_icon.html.twig (там нет lightbulb).
    private const array ICON_PATHS = [
        self::ICON_INFO => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        self::ICON_HELP => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        self::ICON_TIP => '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
    ];

    // Старые метки врезок -> заголовки раздела 26 (v2.3).
    private const array TITLE_ALIASES = [
        'короткий вывод' => 'Коротко',
        'best practice' => 'Совет',
    ];

    // В «Коротко» тезисами считаются 2–4 предложения; больше или меньше -- остаётся абзац.
    private const int SUMMARY_MIN_ITEMS = 2;
    private const int SUMMARY_MAX_ITEMS = 4;

    // Таблица шире экрана прокручивается внутри обёртки, а не ломает страницу.
    private const array TABLE_WRAPPER_ATTRIBUTES = [
        'class' => 'my-6 overflow-x-auto rounded-md border focus-visible:shadow-focus',
        'tabindex' => '0',
        'role' => 'region',
        'aria-label' => 'Таблица',
    ];
    private const string TABLE_CLASS = 'w-full min-w-table border-collapse text-left tabular-nums type-t6';

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
            'h2' => 'mb-5 mt-12 scroll-mt-sticky type-t2-article text-fg first:mt-0',
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
        // Врезки: обёртка, внутренний контейнер, иконка, заголовок и цвет текста.
        $callouts = [
            self::CALLOUT_SUMMARY => [
                'box' => 'my-6 rounded-lg bg-surface-muted p-6',
                'inner' => 'min-w-0 space-y-3',
                'icon' => '',
                'title' => 'type-t3 text-fg',
                'text' => '',
            ],
            self::CALLOUT_INFO => [
                'box' => 'my-6 flex gap-3 rounded-md border border-info-border bg-info-bg p-4',
                'inner' => 'min-w-0 space-y-1',
                'icon' => 'mt-1 size-icon-md shrink-0 text-info',
                'title' => 'type-t5 font-semibold text-info',
                'text' => '',
            ],
            self::CALLOUT_NOTE => [
                'box' => 'my-6 flex gap-3 rounded-md border bg-surface-muted p-4',
                'inner' => 'min-w-0 space-y-1',
                'icon' => 'mt-1 size-icon-md shrink-0 text-fg-muted',
                'title' => 'type-t5 font-semibold text-fg',
                'text' => 'text-fg-secondary',
            ],
        ];

        $html = preg_replace_callback('/<(h2|h3|p|ul|ol|li|a|blockquote|pre|code|table|th|td|hr)(?=[\\s>])([^>]*)>/',
            static function (array $match) use ($classes, $callouts): string {
                $tag = $match[1];
                $attributes = $match[2];
                $class = $classes[$tag];
                $prefix = '';

                if ('blockquote' === $tag && 1 === preg_match('/data-vf-callout="([a-z]+)"(?: data-vf-callout-icon="([a-z]+)")?/', $attributes, $callout) && isset($callouts[$callout[1]])) {
                    $view = $callouts[$callout[1]];
                    $class = $view['box'];
                    $icon = $callout[2] ?? '';
                    if ('' !== $view['icon'] && isset(self::ICON_PATHS[$icon])) {
                        $prefix .= '<svg xmlns="http://www.w3.org/2000/svg" class="'.$view['icon'].'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.self::ICON_PATHS[$icon].'</svg>';
                    }
                    $prefix .= '<div class="'.$view['inner'].'">';
                }
                if (1 === preg_match('/data-vf-callout-(title|part)="([a-z]+)"/', $attributes, $inCallout) && isset($callouts[$inCallout[2]])) {
                    $view = $callouts[$inCallout[2]];
                    $isTitle = 'title' === $inCallout[1];
                    $class = match ($tag) {
                        'p' => $isTitle ? $view['title'] : trim('text-pretty '.$view['text']),
                        'ul' => trim('list-disc pl-5 '.$view['text']),
                        'ol' => trim('list-decimal pl-6 '.$view['text']),
                        default => $class,
                    };
                }
                if ('h3' === $tag && str_contains($attributes, 'data-vf-faq="question"')) {
                    $class = 'mb-2 mt-6 border-t border-border-subtle pt-6 type-t3 text-fg';
                }
                if ('th' === $tag) {
                    $attributes .= ' scope="col"';
                }

                return '<'.$tag.' class="'.$class.'"'.$attributes.'>'.$prefix;
            },
            $html,
        ) ?? $html;

        // Каждая цитата открыта как врезка с внутренним контейнером: закрываем его.
        return str_replace('</blockquote>', '</div></blockquote>', $html);
    }

    /**
     * Вид врезки задаёт жирная метка в начале цитаты: «Коротко» / «Короткий вывод» -- summary,
     * «Важно» -- info, всё остальное («Главный вопрос», «Совет», «Best practice», без метки) -- note.
     * Метка, за которой идёт текст, становится отдельным абзацем-заголовком врезки (без двоеточия).
     */
    private function markCallouts(Node $document): void
    {
        $quotes = [];
        foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if ($node instanceof BlockQuote) {
                $quotes[] = $node;
            }
        }

        foreach ($quotes as $quote) {
            $paragraph = $quote->firstChild();
            $strong = $paragraph instanceof Paragraph ? $paragraph->firstChild() : null;
            $label = $strong instanceof Strong ? mb_strtolower(StringContainerHelper::getChildText($strong)) : '';

            [$kind, $icon] = match (true) {
                str_starts_with($label, 'коротко'), str_starts_with($label, 'короткий вывод') => [self::CALLOUT_SUMMARY, ''],
                str_starts_with($label, 'важно') => [self::CALLOUT_INFO, self::ICON_INFO],
                str_starts_with($label, 'совет'), str_starts_with($label, 'best practice') => [self::CALLOUT_NOTE, self::ICON_TIP],
                default => [self::CALLOUT_NOTE, self::ICON_HELP],
            };
            $quote->data->set('attributes/data-vf-callout', $kind);
            if ('' !== $icon) {
                $quote->data->set('attributes/data-vf-callout-icon', $icon);
            }

            $title = null;
            if ($strong instanceof Strong) {
                $title = $paragraph;
                $rest = $strong->next();
                if (null !== $rest) {
                    $title = new Paragraph();
                    $paragraph->insertBefore($title);
                    $title->appendChild($strong);
                    $last = $strong->lastChild();
                    if ($last instanceof Text) {
                        $literal = rtrim($last->getLiteral(), ': ');
                        $last->setLiteral(self::TITLE_ALIASES[mb_strtolower($literal)] ?? $literal);
                    }
                    if ($rest instanceof Text) {
                        // Текст после метки начинается с заглавной: «сравнивать…» -> «Сравнивать…».
                        $rest->setLiteral(self::capitalize(ltrim($rest->getLiteral())));
                    }
                    if (self::CALLOUT_SUMMARY === $kind) {
                        $this->splitSummaryIntoList($paragraph);
                    }
                }
                $title->data->set('attributes/data-vf-callout-title', $kind);
            }

            foreach ($quote->children() as $child) {
                if ($child !== $title) {
                    $child->data->set('attributes/data-vf-callout-part', $kind);
                }
            }
        }
    }

    /**
     * Тезисы «Коротко»: абзац из простого текста в 2–4 предложения становится маркированным списком.
     */
    private function splitSummaryIntoList(Paragraph $paragraph): void
    {
        $text = '';
        foreach ($paragraph->children() as $child) {
            if (!$child instanceof Text) {
                return;
            }
            $text .= $child->getLiteral();
        }

        $sentences = preg_split('/(?<=[.!?])\s+(?=\p{Lu})/u', trim($text)) ?: [];
        if (\count($sentences) < self::SUMMARY_MIN_ITEMS || \count($sentences) > self::SUMMARY_MAX_ITEMS) {
            return;
        }

        $data = new ListData();
        $data->type = ListBlock::TYPE_BULLET;
        $data->bulletChar = '-';
        $list = new ListBlock($data);
        $list->setTight(true);
        foreach ($sentences as $sentence) {
            $item = new ListItem($data);
            $line = new Paragraph();
            $line->appendChild(new Text(self::capitalize($sentence)));
            $item->appendChild($line);
            $list->appendChild($item);
        }
        $paragraph->replaceWith($list);
    }

    private static function capitalize(string $text): string
    {
        return mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1);
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

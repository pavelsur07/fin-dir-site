<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CookieNoticeTest extends WebTestCase
{
    public function testCookieNoticeLinksToCookieSection(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertSelectorExists('#cookieNotice a[href="/privacy#privacy-cookies"]');
        self::assertSelectorTextContains('#cookieNotice', 'Вебвизор');
    }

    /**
     * Регрессия: после ухода с Bootstrap стиль класса is-visible пропал, и баннер
     * навсегда оставался opacity-0. Видимость держится на data-visible, который
     * ставит navigation.js, -- правила должны быть и в разметке, и в собранном CSS.
     */
    public function testNoticeBecomesVisibleThroughDataVisibleVariant(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertSelectorExists('#cookieNotice[hidden].opacity-0.data-visible\\:opacity-100.data-visible\\:translate-y-0');

        // Крестик позиционируется внутри карточки, а не от fixed-обёртки на всю ширину экрана.
        self::assertSelectorExists('#cookieNotice > div.relative #cookieClose.absolute');
        // Touch target крестика -- не меньше 44px (SITE_RULES, --vf-control-min-height).
        self::assertSelectorExists('#cookieClose.size-control');

        $root = (string) self::getContainer()->getParameter('kernel.project_dir');
        $css = (string) file_get_contents($root.'/public/assets/website/app.css');
        self::assertStringContainsString('.data-visible\\:opacity-100[data-visible]', $css);
        self::assertStringContainsString('.data-visible\\:translate-y-0[data-visible]', $css);

        $script = (string) file_get_contents($root.'/assets/scripts/website/navigation.js');
        self::assertStringContainsString('cookieNotice.dataset.visible', $script);
        self::assertStringNotContainsString('is-visible', $script);
    }

    /**
     * Немодальное уведомление -- landmark region, а не dialog: dialog без
     * перехвата фокуса и Esc вводит скринридер в заблуждение, а aria-live на
     * элементе, который был hidden, не объявляется надёжно.
     */
    public function testNoticeIsLabelledRegionAndMainTakesFocusBack(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertSelectorExists('#cookieNotice[role="region"][aria-labelledby="cookieTitle"]');
        self::assertSelectorExists('#cookieTitle');
        self::assertSelectorNotExists('#cookieNotice[role="dialog"], #cookieNotice[aria-live], #cookieNotice[aria-describedby]');
        // Сюда возвращается фокус после закрытия баннера.
        self::assertSelectorExists('main#main-content[tabindex="-1"]');

        // Закрытие баннера возвращает фокус в main, только если он был внутри баннера, и без прокрутки.
        $script = (string) file_get_contents(self::getContainer()->getParameter('kernel.project_dir').'/assets/scripts/website/navigation.js');
        self::assertStringContainsString('cookieNotice.contains(document.activeElement)', $script);
        self::assertStringContainsString('main.focus({ preventScroll: true })', $script);
    }

    /**
     * WCAG 2.4.11: открытый баннер не должен закрывать элемент в фокусе.
     * sticky в конце body занимает место в потоке (футер не перекрыт), а для
     * элементов в середине страницы navigation.js докручивает прокрутку сам:
     * браузер не прокручивает к элементу, который уже в пределах экрана.
     */
    public function testNoticeDoesNotObscureFocusedElement(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertSelectorExists('body > #cookieNotice.sticky.bottom-4');
        self::assertSelectorNotExists('#cookieNotice.fixed');
        // sticky держит место в конце потока, только если после баннера нет контента.
        $elements = array_values(array_filter(
            $crawler->filter('body > *')->each(static fn ($node): string => $node->nodeName().'#'.$node->attr('id')),
            static fn (string $element): bool => !str_starts_with($element, 'script#'),
        ));
        self::assertSame('div#cookieNotice', end($elements));
        // На короткой странице футер и баннер прижаты к низу экрана, а не висят посередине.
        self::assertSelectorExists('body.flex.min-h-dvh.flex-col > main#main-content.flex-1');

        $script = (string) file_get_contents(self::getContainer()->getParameter('kernel.project_dir').'/assets/scripts/website/navigation.js');
        self::assertStringContainsString("document.addEventListener('focusin', keepFocusAboveNotice)", $script);
        // Только клавиатурный фокус: докрутка под мышью увела бы клик мимо цели.
        self::assertStringContainsString("target.matches(':focus-visible')", $script);
        self::assertStringContainsString("target.closest('dialog[open]')", $script);
        // Обработчик снимается до перевода фокуса в main, иначе страницу унесло бы к низу main.
        self::assertLessThan(
            strpos($script, 'main.focus({ preventScroll: true })'),
            strpos($script, "document.removeEventListener('focusin', keepFocusAboveNotice)"),
        );
    }
}

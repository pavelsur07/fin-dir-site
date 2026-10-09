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

        // Раздел 15: появление fade (только opacity), карточка surface-raised с рамкой border-subtle, radius lg, shadow md.
        self::assertSelectorExists('#cookieNotice[hidden].opacity-0.data-visible\\:opacity-100.duration-base');
        self::assertSelectorNotExists('#cookieNotice.translate-y-4');
        self::assertSelectorExists('#cookieNotice > div.rounded-lg.border.border-border-subtle.bg-surface-raised.shadow-md.p-5.gap-4');
        // Обе кнопки secondary; на узком экране они стоят друг под другом и не создают горизонтальную прокрутку.
        self::assertSelectorExists('#cookieNotice .flex.flex-col.gap-2 #cookieNecessary.flex-1.min-h-11.rounded-md[data-vf-variant="secondary"]');
        self::assertSelectorExists('#cookieNotice .flex.flex-col.gap-2 #cookieAccept.flex-1.min-h-11.rounded-md[data-vf-variant="secondary"]');
        self::assertSelectorNotExists('#cookieClose, #cookieNotice button[aria-label*="Закрыть"], #cookieNotice .bg-overlay');

        $root = (string) self::getContainer()->getParameter('kernel.project_dir');
        $css = (string) file_get_contents($root.'/public/assets/website/app.css');
        self::assertStringContainsString('.data-visible\\:opacity-100[data-visible]', $css);

        $script = (string) file_get_contents($root.'/assets/scripts/website/navigation.js');
        self::assertStringContainsString('cookieNotice.dataset.visible', $script);
        self::assertStringNotContainsString('is-visible', $script);
    }

    /**
     * Выбор хранится 12 месяцев, со сроком; старый ключ «принято» не заставляет показывать баннер заново.
     */
    public function testChoiceIsStoredForTwelveMonthsWithLegacyMigration(): void
    {
        $script = (string) file_get_contents(self::getContainer()->getParameter('kernel.project_dir').'/assets/scripts/website/navigation.js');

        self::assertStringContainsString("const cookieStorageKey = 'vf_cookie_choice'", $script);
        self::assertStringContainsString('365 * 24 * 60 * 60 * 1000', $script);
        self::assertStringContainsString('stored.expires > Date.now()', $script);
        self::assertStringContainsString("chooseCookies('necessary')", $script);
        self::assertStringContainsString("chooseCookies('all')", $script);
        self::assertStringContainsString("localStorage.getItem(legacyStorageKey) === '1'", $script);
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

        self::assertSelectorExists('#cookieNotice[role="region"][aria-label="Уведомление о cookie"]');
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

        // Раздел 15: карточка fixed слева снизу с отступом 16 (ниже md -- на всю ширину минус 16), над липкой шапкой z-40.
        self::assertSelectorExists('body > #cookieNotice.fixed.bottom-4.inset-x-4.z-50.md\\:right-auto.md\\:max-w-modal-sm');
        self::assertSelectorNotExists('#cookieNotice.sticky');
        // Баннер остаётся последним элементом body.
        $elements = array_values(array_filter(
            $crawler->filter('body > *')->each(static fn ($node): string => $node->nodeName().'#'.$node->attr('id')),
            static fn (string $element): bool => !str_starts_with($element, 'script#'),
        ));
        self::assertSame('div#cookieNotice', end($elements));
        // На короткой странице футер и баннер прижаты к низу экрана, а не висят посередине.
        self::assertSelectorExists('body.flex.min-h-dvh.flex-col > main#main-content.flex-1');

        $script = (string) file_get_contents(self::getContainer()->getParameter('kernel.project_dir').'/assets/scripts/website/navigation.js');
        self::assertStringContainsString("document.addEventListener('focusin', keepFocusAboveNotice)", $script);
        // Карточка у левого края мешает только тому, что пересекается с ней по горизонтали.
        self::assertStringContainsString('rect.left < card.right && rect.right > card.left', $script);
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

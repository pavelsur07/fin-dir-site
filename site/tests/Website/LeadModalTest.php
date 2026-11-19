<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Окно записи на разбор: есть только на маркетинговых страницах, форма -- 'popup' с прежним контрактом POST /lead.
 */
final class LeadModalTest extends WebTestCase
{
    private const string MODAL = 'dialog[data-vf-section="lead-modal"]';

    public function testHomePageHasLeadModalWithPopupForm(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        // Анонимный GET с окном не стартует сессию: CSRF формы stateless.
        self::assertSame([], $client->getResponse()->headers->getCookies());
        self::assertSelectorCount(1, self::MODAL);
        // Цель фокуса при автопоказе на сенсорных экранах (клавиатура не поднимается).
        self::assertSelectorExists(self::MODAL.'[tabindex="-1"]');
        self::assertSelectorTextContains(self::MODAL.' [data-vf-lead-modal-response-time]', 'Перезвоним в течение 2 часов в рабочее время');
        self::assertSelectorNotExists('main '.self::MODAL);
        $form = self::MODAL.' form[data-vf-lead-form][method="post"][action="/lead"][data-vf-form-key="popup"]';
        self::assertSelectorExists($form.' input[name="form"][value="popup"]');
        self::assertSelectorExists($form.' input[name="_token"]');
        self::assertSelectorExists($form.' input#lead-modal-name[name="name"][required]');
        self::assertSelectorExists($form.' input[name="contact_type"][value="phone"][checked]');
        self::assertSelectorExists($form.' input[name="contact_type"][value="telegram"]');
        self::assertSelectorExists($form.' input#lead-modal-contact[name="contact"][data-vf-contact-input][required]');
        self::assertSelectorExists($form.' input#lead-modal-agreement[name="agreement"][required]:not([checked])');
        self::assertSelectorNotExists($form.' [name="task"]');
        self::assertSelectorNotExists($form.' [name^="answers"]');
        self::assertSelectorExists($form.' [data-vf-lead-success][hidden]');
        self::assertSelectorExists($form.' [data-vf-lead-error][hidden]');
        self::assertSelectorTextContains(self::MODAL.' h2#lead-modal-title', 'Покажем, где ваш бизнес теряет прибыль');
        self::assertSelectorExists('script[src^="/assets/website/lead-modal.js?v="][defer]');
        // Кнопки-ссылки к форме открывают окно; без JS остаются ссылками на #lead-form.
        self::assertSelectorExists('main a[href="#lead-form"][data-vf-lead-modal-open]');
    }

    public function testNonMarketingPagesHaveNoLeadModal(): void
    {
        $client = static::createClient();

        foreach (['/privacy', '/gazeta', '/not-existing-page'] as $path) {
            $client->request('GET', $path);

            self::assertSelectorNotExists(self::MODAL, $path);
            self::assertSelectorNotExists('script[src^="/assets/website/lead-modal.js"]', $path);
        }
    }
}

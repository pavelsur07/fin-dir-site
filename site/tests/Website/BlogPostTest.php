<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BlogPostTest extends WebTestCase
{
    public function testBlogIndexRendersWithoutBootstrap(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/gazeta');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1');
        self::assertSelectorTextContains('h1', 'Газета');
        self::assertSelectorCount(0, 'link[href*="bootstrap"], script[src*="bootstrap"]');
        self::assertSelectorExists('[data-vf-component="navbar"]');
        self::assertSelectorExists('[data-vf-component="footer"]');
    }

    public function testBlogPostRendersProductionLayoutAndLeadForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/gazeta/post-1');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1');
        self::assertSelectorTextContains('h1', 'Маркетплейс или интернет-магазин');
        self::assertSelectorCount(0, 'link[href*="bootstrap"], script[src*="bootstrap"]');
        self::assertSelectorExists('[data-vf-component="navbar"]');
        self::assertSelectorExists('[data-vf-component="footer"]');
        self::assertSelectorExists('[data-vf-component="breadcrumb"]');
        self::assertSelectorExists('article#article');
        self::assertSelectorExists('aside [href="#intro"]');
        self::assertSelectorExists('form.js-lead-form');
        self::assertSelectorExists('form.js-lead-form input[name="name"]');
        self::assertSelectorExists('form.js-lead-form input[name="contact"]');
        self::assertSelectorExists('form.js-lead-form input[name="email"]');
        self::assertSelectorExists('form.js-lead-form input[name="consent"]');
        self::assertSelectorExists('.js-lead-success.hidden');
        self::assertSelectorExists('section#related');

        $html = $crawler->html();
        self::assertStringNotContainsString('bi-', $html);
        self::assertStringNotContainsString('data-bs-', $html);
        self::assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $html);
        self::assertDoesNotMatchRegularExpression('/<script\b(?![^>]*\bsrc\s*=)(?![^>]*type\s*=\s*"application\/ld\+json")[^>]*>/i', $html);
    }
}

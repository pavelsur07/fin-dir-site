<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Controller;

use App\Documentation\Entity\DocPage;
use App\Documentation\ValueObject\DocLinkType;
use App\Tests\Documentation\Builder\DocPageBuilder;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DocumentationBrowserFixturesTest extends WebTestCase
{
    public function testRenderPublicAndAdminScreensWithLongContent(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection('browser-docs');
        $em->persist($section);
        $target = DocPageBuilder::aPage($section, 'browser-next', true, 2);
        $em->persist($target);
        $em->flush();
        $now = new \DateTimeImmutable('2026-01-01');
        $page = new DocPage('browser-long', $now);
        $body = "## Настройка\n\nСсылка на [следующий материал](doc:".$target->id().").\n\n| Поле | Значение |\n| --- | --- |\n| Ссылка | ".str_repeat('ДлинноеЗначение', 30)." |\n\n```\n".str_repeat('long-code-', 40)."\n```";
        $page->edit($section, str_repeat('ДлинныйЗаголовок', 12), 'browser-long', 'Инструкция с таблицей, ссылками и оглавлением.', $body, 'Настройка отчёта', 0, null, null, $now);
        $page->replaceLinks([['target' => $target, 'type' => DocLinkType::INLINE, 'fragment' => '', 'position' => 0, 'urls' => ['doc:'.$target->id()]], ['target' => $target, 'type' => DocLinkType::RELATED, 'fragment' => '', 'position' => 0, 'urls' => []]]);
        $page->publish($now);
        $em->persist($page);
        $em->flush();
        $paths = ['home' => 'https://docs.vashfindir.ru/', 'section' => 'https://docs.vashfindir.ru/sections/browser-docs', 'article' => 'https://docs.vashfindir.ru/articles/browser-long', 'next' => 'https://docs.vashfindir.ru/articles/browser-next', 'search' => 'https://docs.vashfindir.ru/search?q=операции'];
        $dir = getenv('DOCS_BROWSER_FIXTURES');
        $capture = function (string $name, string $url) use ($client, $dir): void {
            $client->request('GET', $url);
            self::assertResponseIsSuccessful();
            self::assertSelectorCount(1, 'h1');
            if (false !== $dir && '' !== $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                } file_put_contents($dir.'/'.$name.'.html', $client->getResponse()->getContent());
            }
        };
        foreach ($paths as $name => $url) {
            $capture($name, $url);
        }
        $client->request('GET', 'https://vashfindir.ru/admin/login');
        $client->submitForm('Войти', ['_username' => 'admin', '_password' => 'test-password']);
        self::assertResponseRedirects('/admin');
        foreach (['admin-pages' => '/admin/docs/pages', 'admin-form' => '/admin/docs/pages/'.$page->id().'/edit', 'admin-preview' => '/admin/docs/pages/'.$page->id().'/preview', 'picker' => '/admin/docs/link-picker?target='.$page->id()] as $name => $path) {
            $capture($name,'https://vashfindir.ru'.$path);
        }
    }
}

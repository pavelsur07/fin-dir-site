<?php

declare(strict_types=1);

namespace App\Tests\Admin;

use App\Tests\ClientCase\Builder\ClientCaseBuilder;
use App\Tests\Lead\Builder\LeadBuilder;
use App\Tests\Publication\Builder\PostBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Рендерит реальные страницы с тестовыми данными; DAMA откатывает записи. */
final class AdminBrowserFixturesTest extends WebTestCase
{
    public function testAdminPagesRenderWithLongValuesAndAllRowActions(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $post = PostBuilder::aPost()->withSlug('browser-long-post')->withTitle(str_repeat('ЗаголовокБезПробелов', 10))->build();
        $case = ClientCaseBuilder::aCase()->withSlug('browser-long-case')->withTitle(str_repeat('ЗаголовокБезПробелов', 10))->build();
        $lead = LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000099')->withContact(str_repeat('a', 120).'@example.test')->build();
        foreach ([
            $post, $case, $lead,
            PostBuilder::aPost()->withSlug('browser-second-post')->published()->build(),
            ClientCaseBuilder::aCase()->withSlug('browser-second-case')->published()->build(),
            LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000100')->build(),
        ] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        $pages = ['login' => '/admin/login'];
        $this->capture($client, $pages);
        $client->submitForm('Войти', ['_username' => 'admin', '_password' => 'test-password']);
        self::assertResponseRedirects('/admin');
        $this->capture($client, [
            'dashboard' => '/admin',
            'posts' => '/admin/posts',
            'cases' => '/admin/cases',
            'leads' => '/admin/leads',
            'post-form' => '/admin/posts/'.$post->id().'/edit',
            'case-form' => '/admin/cases/'.$case->id().'/edit',
            'lead-card' => '/admin/leads/'.$lead->id(),
        ]);
    }

    /** @param array<string, string> $pages */
    private function capture(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, array $pages): void
    {
        $directory = getenv('ADMIN_BROWSER_FIXTURES');
        foreach ($pages as $name => $path) {
            $client->request('GET', $path);
            self::assertResponseIsSuccessful();
            self::assertSelectorCount(1, 'h1');
            self::assertSelectorNotExists('script[src^="/assets/website/"], link[rel="stylesheet"][href^="/assets/website/"]');
            if (false !== $directory && '' !== $directory) {
                if (!is_dir($directory)) {
                    mkdir($directory, 0775, true);
                }
                file_put_contents($directory.'/'.$name.'.html', $client->getResponse()->getContent());
            }
        }
    }
}

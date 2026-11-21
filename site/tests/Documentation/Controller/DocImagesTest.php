<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Controller;

use App\Tests\Documentation\Builder\DocPageBuilder;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class DocImagesTest extends WebTestCase
{
    public function testGuestCannotReadAttachmentGallery(): void
    {
        $client = self::createClient([], ['HTTP_HOST' => 'vashfindir.ru']);
        $client->request('GET', '/admin/docs/pages/1/images');
        self::assertResponseRedirects('/admin/login');
        $client->request('GET', 'https://docs.vashfindir.ru/media/documentation/not-a-uuid');
        self::assertResponseStatusCodeSame(404);
        self::assertStringContainsString('no-store', (string) $client->getResponse()->headers->get('Cache-Control'));
    }

    public function testUploadEmbedPublishAndRevokeAccess(): void
    {
        $client = self::createClient([], ['HTTP_HOST' => 'vashfindir.ru']);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $page = DocPageBuilder::aPage($section);
        $other = DocPageBuilder::aPage($section, 'another-report');
        $em->persist($section);
        $em->persist($page);
        $em->persist($other);
        $em->flush();

        $editor = '/admin/docs/pages/'.$page->id().'/edit';
        $upload = '/admin/docs/pages/'.$page->id().'/images';
        $article = 'https://docs.vashfindir.ru/articles/'.$page->slug();

        $provider = self::getContainer()->get('security.user.provider.concrete.admin_user');
        $client->loginUser($provider->loadUserByIdentifier('admin'), 'admin');
        $crawler = $client->request('GET', $editor);
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('[data-doc-image-upload] [name="_token"]')->attr('value');
        self::assertNotNull($token);

        $client->request('POST', $upload);
        self::assertResponseStatusCodeSame(403);

        $file = tempnam(sys_get_temp_dir(), 'doc-image-test-');
        self::assertIsString($file);
        try {
            file_put_contents($file, 'invalid data');
            $client->request('POST', $upload, ['_token' => $token], ['image' => new UploadedFile($file, 'test.png', 'image/png', null, true)], ['HTTP_ACCEPT' => 'application/json']);
            self::assertResponseStatusCodeSame(422);

            $png = imagecreatetruecolor(32, 16);
            self::assertInstanceOf(\GdImage::class, $png);
            self::assertTrue(imagepng($png, $file));
            $received = null;
            for ($i = 0; $i < 2; ++$i) {
                $client->request('POST', $upload, ['_token' => $token], ['image' => new UploadedFile($file, 'test.png', 'image/png', null, true)], ['HTTP_ACCEPT' => 'application/json']);
                self::assertResponseIsSuccessful();
                $response = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                if (null !== $received) {
                    self::assertSame($received, $response);
                }
                $received = $response;
            }
            self::assertIsArray($received);
            self::assertSame(32, $received['width']);
        } finally {
            unlink($file);
        }

        $image = 'https://docs.vashfindir.ru/media/documentation/'.$received['id'];
        $adminImage = 'https://vashfindir.ru/admin/docs/images/'.$received['id'];
        $body = '![Снимок формы]('.$received['markdownDestination'].')';

        $client->request('GET', $image);
        self::assertResponseStatusCodeSame(404);
        $client->request('GET', $adminImage);
        self::assertResponseHeaderSame('Content-Type', 'image/webp');
        self::assertStringContainsString('no-store', (string) $client->getResponse()->headers->get('Cache-Control'));
        $bytes = (string) $client->getResponse()->getContent();
        self::assertSame([32, 16], array_slice((array) getimagesizefromstring($bytes), 0, 2));

        $client->request('GET', '/admin/docs/pages/'.$other->id().'/edit');
        $client->submitForm('Сохранить страницу', ['doc_page[body]' => $body]);
        self::assertResponseStatusCodeSame(409);
        $client->request('GET', $editor);
        $client->submitForm('Сохранить страницу', ['doc_page[body]' => '![]('.$received['markdownDestination'].')']);
        self::assertResponseStatusCodeSame(409);
        $client->request('GET', $editor);
        $client->submitForm('Сохранить страницу', ['doc_page[body]' => $body]);
        self::assertResponseRedirects($editor);
        $client->request('GET', '/admin/docs/pages/'.$page->id().'/preview');
        self::assertSelectorExists('img[src="'.$adminImage.'"][alt="Снимок формы"]');

        $client->request('GET', $editor);
        $client->submitForm('Опубликовать');
        self::assertResponseRedirects($editor);
        $client->request('GET', $article);
        self::assertSelectorExists('img[src="'.$image.'"][width="32"][height="16"]');
        self::assertSelectorExists('a[data-doc-image-preview][href="'.$image.'"][target="_blank"] img');
        self::assertSelectorCount(1, 'dialog[data-doc-image-dialog]');
        self::assertSelectorExists('script[src^="/assets/website/doc-image-viewer.js?v="][defer]');
        $client->request('GET', $image);
        self::assertResponseHeaderSame('Content-Type', 'image/webp');
        self::assertSame($bytes, $client->getResponse()->getContent());
        self::assertSame([], $client->getResponse()->headers->getCookies());
        $etag = $client->getResponse()->headers->get('ETag');
        self::assertNotNull($etag);
        $client->request('GET', $image, server: ['HTTP_IF_NONE_MATCH' => $etag]);
        self::assertResponseStatusCodeSame(304);

        $client->request('GET', 'https://vashfindir.ru'.$editor);
        $client->submitForm('Снять с публикации');
        self::assertResponseRedirects($editor);
        $client->request('GET', $image, server: ['HTTP_IF_NONE_MATCH' => $etag]);
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', 'https://vashfindir.ru'.$editor);
        $client->submitForm('Опубликовать');
        self::assertResponseRedirects($editor);
        $client->request('GET', 'https://vashfindir.ru'.$editor);
        self::assertSelectorNotExists('[data-doc-image-delete]');
        $client->submitForm('Сохранить страницу', ['doc_page[body]' => 'Инструкция доступна в текстовом виде.']);
        self::assertResponseRedirects($editor);
        $client->request('GET', $image, server: ['HTTP_IF_NONE_MATCH' => $etag]);
        self::assertResponseStatusCodeSame(404);
        $client->request('GET', $article);
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('img[src="'.$image.'"]');
        $client->request('GET', 'https://vashfindir.ru'.$upload);
        $client->submitForm('Подтвердить удаление');
        self::assertResponseRedirects($upload, 303);
        $client->request('GET', $adminImage);
        self::assertResponseStatusCodeSame(404);
    }
}

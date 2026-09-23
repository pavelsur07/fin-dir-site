<?php

declare(strict_types=1);

namespace App\Tests\Publication\Service;

use App\Publication\DTO\PostInput;
use App\Publication\Entity\Post;
use App\Publication\Exception\PostSlugAlreadyTaken;
use App\Publication\Exception\PostWasModified;
use App\Publication\Query\PostEditData\PostEditDataQuery;
use App\Publication\Service\PostCreator;
use App\Publication\Service\PostEditor;
use App\Publication\Service\PostStatusChanger;
use App\Publication\ValueObject\PostSlug;
use App\Tests\Publication\Builder\PostBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Сценарии записи на реальной базе: уникальность slug и оптимистичная блокировка
 * держатся на Postgres, mock-и здесь ничего бы не проверили.
 */
final class PostWriteScenariosTest extends KernelTestCase
{
    public function testGeneratedSlugGetsSuffixWhenTaken(): void
    {
        $this->persist(PostBuilder::aPost()->withSlug('biznes-plan')->build());

        $id = $this->creator()->create($this->input('Бизнес-план'));

        self::assertSame('biznes-plan-2', $this->editData()->get($id)->slug);
    }

    public function testSuffixedSlugOfLongTitleFitsIntoColumn(): void
    {
        // 156 символов заголовка -- больше 150 символов slug после транслитерации.
        $title = str_repeat('Щедрый отчёт ', 12);

        $this->creator()->create($this->input($title));
        $id = $this->creator()->create($this->input($title));

        $slug = $this->editData()->get($id)->slug;
        self::assertSame(PostSlug::MAX_LENGTH, \strlen($slug));
        self::assertStringEndsWith('-2', $slug);
    }

    public function testStatusChangeRacingWithAnotherSaveIsRejected(): void
    {
        $id = $this->creator()->create($this->input('Статья'));
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        // Статья уже в Unit of Work, а в базе её тем временем сохранили заново.
        $entityManager->find(Post::class, $id);
        $entityManager->getConnection()->executeStatement('UPDATE publication_post SET version = version + 1 WHERE id = ?', [$id]);

        $this->expectException(PostWasModified::class);
        self::getContainer()->get(PostStatusChanger::class)->publish($id);
    }

    public function testExplicitTakenSlugIsRejected(): void
    {
        $this->persist(PostBuilder::aPost()->withSlug('taken')->build());

        $input = $this->input('Другая статья');
        $input->slug = 'taken';

        $this->expectException(PostSlugAlreadyTaken::class);
        $this->creator()->create($input);
    }

    public function testEditWithStaleVersionIsRejected(): void
    {
        $id = $this->creator()->create($this->input('Статья'));
        $opened = PostInput::fromEditData($this->editData()->get($id));

        // Кто-то сохранил статью после того, как её открыли.
        $other = PostInput::fromEditData($this->editData()->get($id));
        $other->title = 'Правка из другого окна';
        $this->editor()->edit($id, $other);
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        $opened->title = 'Моя правка';

        $this->expectException(PostWasModified::class);
        $this->editor()->edit($id, $opened);
    }

    public function testEditBumpsVersion(): void
    {
        $id = $this->creator()->create($this->input('Статья'));
        $input = PostInput::fromEditData($this->editData()->get($id));
        $input->title = 'Новый заголовок';

        $this->editor()->edit($id, $input);

        $data = $this->editData()->get($id);
        self::assertSame('Новый заголовок', $data->title);
        self::assertSame(($input->version ?? 0) + 1, $data->version);
    }

    private function input(string $title): PostInput
    {
        $input = new PostInput();
        $input->title = $title;
        $input->body = 'Текст';

        return $input;
    }

    private function persist(object $entity): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($entity);
        $entityManager->flush();
        $entityManager->clear();
    }

    private function creator(): PostCreator
    {
        return self::getContainer()->get(PostCreator::class);
    }

    private function editor(): PostEditor
    {
        return self::getContainer()->get(PostEditor::class);
    }

    private function editData(): PostEditDataQuery
    {
        return self::getContainer()->get(PostEditDataQuery::class);
    }
}

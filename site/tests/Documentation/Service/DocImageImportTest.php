<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Service;

use App\Documentation\Command\DocImageImportCommand;
use App\Tests\Documentation\Builder\DocPageBuilder;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class DocImageImportTest extends KernelTestCase
{
    public function testStdinImportIsIdempotentAndLimitedToDrafts(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $draft = DocPageBuilder::aPage($section);
        $published = DocPageBuilder::aPage($section, 'published', true);
        $em->persist($section);
        $em->persist($draft);
        $em->persist($published);
        $em->flush();

        $pixel = imagecreatetruecolor(8, 16);
        self::assertInstanceOf(\GdImage::class, $pixel);
        ob_start();
        try {
            self::assertTrue(imagepng($pixel));
            $png = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }

        [$code, $result] = $this->import($draft->id(), $png);
        self::assertSame(Command::SUCCESS, $code);
        $data = json_decode($result, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('doc-image:'.$data['id'], $data['markdownDestination']);
        self::assertSame(16, $data['height']);
        self::assertSame([$code, $result], $this->import($draft->id(), $png));
        self::assertSame(Command::FAILURE, $this->import($published->id(), $png)[0]);
        self::assertSame(Command::FAILURE, $this->import($draft->id(), '')[0]);
        self::assertSame(Command::FAILURE, $this->import($draft->id(), str_repeat('a', 8 * 1024 * 1024 + 1))[0]);
        self::assertEquals(1, $em->getConnection()->fetchOne('SELECT COUNT(*) FROM documentation_image'));
    }

    /** @return array{int,string} */
    private function import(int $pageId, string $bytes): array
    {
        $command = self::getContainer()->get(DocImageImportCommand::class);
        $input = new ArrayInput(['--page' => (string) $pageId]);
        $output = new BufferedOutput();
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);
        try {
            fwrite($stream, $bytes);
            rewind($stream);
            $input->setStream($stream);

            return [$command->run($input, $output), $output->fetch()];
        } finally {
            fclose($stream);
        }
    }
}

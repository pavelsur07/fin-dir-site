<?php

declare(strict_types=1);

namespace App\Tests\Lead\Functional;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class NotifyPendingLeadsCommandTest extends KernelTestCase
{
    public function testWithoutTelegramSettingsCommandExplainsAndSucceeds(): void
    {
        $tester = new CommandTester(new Application(self::bootKernel())->find('app:lead:notify-pending'));

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Уведомления выключены', $tester->getDisplay());
    }
}

<?php

declare(strict_types=1);

namespace App\Lead\Command;

use App\Lead\Service\ExpiredLeadPurger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Запускать раз в сутки: согласие обещает уничтожить данные в течение 30 дней после
 * истечения срока. За запуск удаляется не больше LIMIT обращений, остальные -- следующим.
 */
#[AsCommand(name: 'app:lead:purge-expired', description: 'Удаляет обращения, срок хранения которых истёк (3 года с даты согласия)')]
final class PurgeExpiredLeadsCommand extends Command
{
    private const int LIMIT = 200;

    public function __construct(private readonly ExpiredLeadPurger $purger)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->purger->purge(self::LIMIT);

        $output->writeln(\sprintf('Удалено обращений: %d.', $result['erased']));
        if ([] !== $result['crmLeadIds']) {
            $output->writeln('Удалите вручную лиды в Битрикс24: '.implode(', ', $result['crmLeadIds']).'.');
        }

        return Command::SUCCESS;
    }
}

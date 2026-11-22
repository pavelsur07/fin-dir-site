<?php

declare(strict_types=1);

namespace App\Lead\Command;

use App\Lead\Service\LeadCrmExporter;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:lead:export-crm-pending', description: 'Досылает в Битрикс24 обращения, которые не ушли')]
final class ExportPendingLeadsToCrmCommand extends Command
{
    private const string WINDOW = '-7 days';
    private const int LIMIT = 50;

    public function __construct(
        private readonly LeadCrmExporter $exporter,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->exporter->isEnabled()) {
            $output->writeln('Передача в Битрикс24 выключена или не настроена.');

            return Command::SUCCESS;
        }

        $result = $this->exporter->exportPending($this->clock->now()->modify(self::WINDOW), self::LIMIT);
        $output->writeln(\sprintf('Передано: %d, не удалось: %d.', $result['sent'], $result['failed']));

        return 0 === $result['failed'] ? Command::SUCCESS : Command::FAILURE;
    }
}

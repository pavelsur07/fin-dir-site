<?php

declare(strict_types=1);

namespace App\Lead\Command;

use App\Lead\Service\LeadNotificationSender;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:lead:notify-pending', description: 'Досылает уведомления о заявках, которые не ушли в Telegram')]
final class NotifyPendingLeadsCommand extends Command
{
    private const string WINDOW = '-7 days';
    private const int LIMIT = 50;

    public function __construct(
        private readonly LeadNotificationSender $sender,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->sender->isEnabled()) {
            $output->writeln('Уведомления выключены: TELEGRAM_BOT_TOKEN или TELEGRAM_LEAD_CHAT_ID не заданы.');

            return Command::SUCCESS;
        }

        $result = $this->sender->sendPending($this->clock->now()->modify(self::WINDOW), self::LIMIT);
        $output->writeln(\sprintf('Отправлено: %d, не удалось: %d.', $result['sent'], $result['failed']));

        return 0 === $result['failed'] ? Command::SUCCESS : Command::FAILURE;
    }
}

<?php

declare(strict_types=1);

namespace App\Documentation\Command;

use App\Documentation\DTO\DocMcpHttpSettings;
use App\Documentation\Service\DocMcpClientGrantEditor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:docs:mcp:client:disable', description: 'disable a registered external MCP client locally.')]
final class DocMcpClientDisableCommand extends Command
{
    public function __construct(private readonly DocMcpClientGrantEditor $editor, private readonly DocMcpHttpSettings $settings)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('client-id', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->editor->disable($this->settings->issuer, $input->getArgument('client-id'));
        $output->writeln('Client grant updated.');

        return Command::SUCCESS;
    }
}

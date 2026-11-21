<?php

declare(strict_types=1);

namespace App\Documentation\Command;

use App\Documentation\Mcp\DocMcpAccess;
use App\Documentation\Mcp\DocMcpServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:docs:mcp', description: 'Serve Documentation MCP over trusted local stdio.')]
final class DocMcpCommand extends Command
{
    public function __construct(
        private readonly DocMcpServer $server,
        #[Autowire('%env(bool:DOCS_MCP_ENABLED)%')] private readonly bool $enabled,
        #[Autowire('%env(DOCS_MCP_ACTOR)%')] private readonly string $actor,
        #[Autowire('%env(DOCS_MCP_PROFILE)%')] private readonly string $profile,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->enabled) {
            fwrite(STDERR, "Documentation MCP is disabled.\n");

            return Command::FAILURE;
        }
        try {
            $access = new DocMcpAccess($this->actor, $this->profile);
        } catch (\InvalidArgumentException) {
            fwrite(STDERR, "Documentation MCP requires a valid actor and profile.\n");

            return Command::FAILURE;
        }

        return $this->server->run($access);
    }
}

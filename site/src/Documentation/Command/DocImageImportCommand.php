<?php

declare(strict_types=1);

namespace App\Documentation\Command;

use App\Documentation\DTO\DocImageUpload;
use App\Documentation\Exception\DocConflict;
use App\Documentation\Exception\DocNotFound;
use App\Documentation\Service\DocImageUploader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:docs:image:import', description: 'Import a screenshot from stdin into a documentation draft')]
final class DocImageImportCommand extends Command
{
    public function __construct(private readonly DocImageUploader $uploader)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('page', null, InputOption::VALUE_REQUIRED, 'Documentation draft ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $page = filter_var($input->getOption('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        $stderr = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        if (false === $page) {
            $stderr->writeln('Укажите ID черновика в --page.');

            return Command::INVALID;
        }

        $stream = $input instanceof StreamableInputInterface ? $input->getStream() : null;
        $contents = stream_get_contents($stream ?? STDIN, DocImageUpload::MAX_INPUT_BYTES + 1);
        try {
            $image = $this->uploader->upload($page, false === $contents ? '' : $contents, draftOnly: true);
        } catch (DocConflict|DocNotFound $error) {
            $stderr->writeln($error->getMessage(), OutputInterface::OUTPUT_RAW);

            return Command::FAILURE;
        }

        $output->writeln(json_encode($image->toArray(), JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);

        return Command::SUCCESS;
    }
}

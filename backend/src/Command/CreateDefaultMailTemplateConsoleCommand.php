<?php

declare(strict_types=1);

namespace App\Command;

use App\Command\Application\CreateDefaultMailTemplateCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:templates:create-default',
    description: 'Create the default email template when it does not exist.',
)]
final class CreateDefaultMailTemplateConsoleCommand extends Command
{
    public function __construct(
        private readonly CreateDefaultMailTemplateCommand $createDefaultMailTemplate,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ($this->createDefaultMailTemplate)();
        $output->writeln('Default mail template is ready.');

        return Command::SUCCESS;
    }
}

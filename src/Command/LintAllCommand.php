<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:lint:all',
    description: 'Runs all lint and static analysis commands',
)]
class LintAllCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hasErrors = false;

        $commands = [
            ['php', 'bin/console', 'lint:container'],
            ['php', 'bin/console', 'lint:twig', 'templates'],
            ['php', 'bin/console', 'lint:yaml', 'config'],
            ['vendor/bin/phpstan', 'analyse', '--level=3', 'src'],
            ['vendor/bin/php-cs-fixer', 'fix', '--diff', '--dry-run'],
        ];

        foreach ($commands as $command) {
            $io->section('Running '.implode(' ', $command));

            $process = new Process($command);
            $process->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                $hasErrors = true;
            }
        }

        if ($hasErrors) {
            $io->error('Some checks failed. Please fix the issues before committing.');

            return Command::FAILURE;
        }

        $io->success('All checks passed successfully.');

        return Command::SUCCESS;
    }
}

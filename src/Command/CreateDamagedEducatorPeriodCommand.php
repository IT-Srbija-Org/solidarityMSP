<?php

namespace App\Command;

use App\Entity\DamagedEducatorPeriod;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-damaged-educator-period',
    description: 'Create damaged educator period',
)]
class CreateDamagedEducatorPeriodCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('month', InputArgument::REQUIRED, 'Month number (1-12)')
            ->addArgument('year', InputArgument::REQUIRED, 'Year (2020-2030)')
            ->addArgument('type', InputArgument::REQUIRED, '"first", "second", "full"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $month = (int) $input->getArgument('month');
        $year = (int) $input->getArgument('year');
        $type = $input->getArgument('type');

        // Validate month
        if ($month < 1 || $month > 12) {
            $io->error('Month must be between 1 and 12');

            return Command::FAILURE;
        }

        // Validate year
        if ($year < 2025) {
            $io->error('Year must be greater than 2025');

            return Command::FAILURE;
        }

        if ('first' !== DamagedEducatorPeriod::TYPE_FIRST_HALF && 'second' !== DamagedEducatorPeriod::TYPE_SECOND_HALF && 'full' !== DamagedEducatorPeriod::TYPE_FULL) {
            $io->error('Type must be "first-half", "second-half" or "full"');
        }

        // Validate not in future
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        if ($year > $currentYear || ($year == $currentYear && $month > $currentMonth)) {
            $io->error('Cannot create period in the future');

            return Command::FAILURE;
        }

        // Check if period already exists
        $entity = $this->entityManager->getRepository(DamagedEducatorPeriod::class)->findOneBy([
            'month' => $month,
            'year' => $year,
            'type' => $type,
        ]);

        if ($entity) {
            $io->success('Period already exists');

            return Command::SUCCESS;
        }

        // Create new period
        $entity = new DamagedEducatorPeriod();
        $entity->setMonth($month);
        $entity->setYear($year);
        $entity->setType($type);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $io->success(sprintf('Created new period: %s %d %d', $type, $month, $year));

        return Command::SUCCESS;
    }
}

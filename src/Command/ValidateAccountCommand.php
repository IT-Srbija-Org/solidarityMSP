<?php

namespace App\Command;

use App\Entity\Educator;
use App\Validator\Mod97;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:validate:account',
    description: 'Validates bank account numbers in the database using mod97',
)]
class ValidateAccountCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $educators = $this->entityManager->getRepository(Educator::class)->findAll();
        $mod97Constraint = new Mod97();
        $invalidCount = 0;

        foreach ($educators as $educator) {
            $violations = $this->validator->validate($educator->getAccountNumber(), $mod97Constraint);
            if (count($violations) > 0) {
                ++$invalidCount;
                $io->error(sprintf(
                    'Invalid account number found in Educator (ID: %d, Name: %s): %s',
                    $educator->getId(),
                    $educator->getName(),
                    $educator->getAccountNumber()
                ));
            }
        }

        if ($invalidCount > 0) {
            $io->warning(sprintf('Validation completed. Found %d invalid account numbers.', $invalidCount));

            return Command::FAILURE;
        }

        $io->success('All account numbers are valid.');

        return Command::SUCCESS;
    }
}

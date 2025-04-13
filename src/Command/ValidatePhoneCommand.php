<?php

namespace App\Command;

use App\Entity\UserDelegateRequest;
use App\Validator\Phone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:validate:phone',
    description: 'Validates phone numbers in the database',
)]
class ValidatePhoneCommand extends Command
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

        // Validate phone numbers
        $requests = $this->entityManager->getRepository(UserDelegateRequest::class)->findAll();
        $phoneConstraint = new Phone();
        $invalidCount = 0;

        foreach ($requests as $request) {
            $violations = $this->validator->validate($request->getPhone(), $phoneConstraint);
            if (count($violations) > 0) {
                ++$invalidCount;
                $io->error(sprintf(
                    'Invalid phone number found in UserDelegateRequest (ID: %d): %s',
                    $request->getId(),
                    $request->getPhone()
                ));
            }
        }

        if ($invalidCount > 0) {
            $io->warning(sprintf('Validation completed. Found %d invalid phone numbers.', $invalidCount));

            return Command::FAILURE;
        }

        $io->success('All phone numbers are valid.');

        return Command::SUCCESS;
    }
}

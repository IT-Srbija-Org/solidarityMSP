<?php

namespace App\Command;

use App\Entity\DamagedEducator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:update-damaged-educator-city',
    description: 'Update city field in DamagedEducator based on School\'s city',
)]
class UpdateDamagedEducatorCityCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $damagedEducatorRepository = $this->entityManager->getRepository(DamagedEducator::class);
        $countUpdated = 0;

        while (true) {
            /** @var DamagedEducator[] $damagedEducators */
            $damagedEducators = $damagedEducatorRepository->findBy([
                'city' => null,
            ], [], 500);

            if (empty($damagedEducators)) {
                break;
            }

            foreach ($damagedEducators as $damagedEducator) {
                $school = $damagedEducator->getSchool();
                $damagedEducator->setCity($school->getCity());
                $this->entityManager->flush();

                $output->writeln('Update DamagedEducator ID: '.$damagedEducator->getId());
                ++$countUpdated;
            }

            $this->entityManager->clear();
        }

        $output->writeln(sprintf('Total updated %d DamagedEducator entities.', $countUpdated));

        return Command::SUCCESS;
    }
}

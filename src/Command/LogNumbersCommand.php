<?php

namespace App\Command;

use App\Entity\LogNumber;
use App\Entity\User;
use App\Entity\UserDelegateSchool;
use App\Entity\UserDonor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

#[AsCommand(
    name: 'app:log-numbers',
    description: 'Log numbers (donors, monthly donors, monthly amount, delegates, monthly delegates)',
)]
class LogNumbersCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Command started at '.date('Y-m-d H:i:s'));

        $store = new FlockStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock($this->getName(), 0);
        if (!$lock->acquire()) {
            return Command::FAILURE;
        }

        $userDonorRepository = $this->entityManager->getRepository(UserDonor::class);
        $userRepository = $this->entityManager->getRepository(User::class);
        $userDelegateSchoolRepository = $this->entityManager->getRepository(UserDelegateSchool::class);

        $totalDonors = $userDonorRepository->getTotal();
        $totalMonthlyDonors = $userDonorRepository->getTotalMonthly();
        $sumAmountMonthlyDonors = $userDonorRepository->sumAmountMonthlyDonors();
        $totalDelegates = $userRepository->getTotalDelegates();
        $totalActiveSchools = $userDelegateSchoolRepository->getTotalActiveSchools();

        $entity = new LogNumber();
        $entity->setTotalDonors($totalDonors);
        $entity->setTotalMonthlyDonors($totalMonthlyDonors);
        $entity->setSumAmountMonthlyDonors($sumAmountMonthlyDonors);
        $entity->setTotalDelegates($totalDelegates);
        $entity->setTotalActiveSchools($totalActiveSchools);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

}

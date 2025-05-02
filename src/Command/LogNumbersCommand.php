<?php

namespace App\Command;

use App\Entity\LogNumber;
use App\Repository\UserDelegateSchoolRepository;
use App\Repository\UserDonorRepository;
use App\Repository\UserRepository;
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
    public function __construct(private EntityManagerInterface $entityManager, private UserDonorRepository $userDonorRepository, private UserRepository $userRepository, private UserDelegateSchoolRepository $userDelegateSchoolRepository)
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

        $totalDonors = $this->userDonorRepository->getTotal();
        $totalMonthlyDonors = $this->userDonorRepository->getTotalMonthly();
        $totalNonMonthlyDonors = $this->userDonorRepository->getTotalNonMonthly();
        $sumAmountMonthlyDonors = $this->userDonorRepository->sumAmountMonthlyDonors();
        $sumAmountNonMonthlyDonors = $this->userDonorRepository->sumAmountNonMonthlyDonors();
        $totalDelegates = $this->userRepository->getTotalDelegates();
        $totalActiveSchools = $this->userDelegateSchoolRepository->getTotalActiveSchools();

        $entity = $this->entityManager->getRepository(LogNumber::class)->findOneBy(['createdAt' => new \DateTime()]);
        if (!$entity) {
            $entity = new LogNumber();
        }

        $entity->setTotalDonors($totalDonors);
        $entity->setTotalMonthlyDonors($totalMonthlyDonors);
        $entity->setTotalNonMonthlyDonors($totalNonMonthlyDonors);
        $entity->setSumAmountMonthlyDonors($sumAmountMonthlyDonors);
        $entity->setSumAmountNonMonthlyDonors($sumAmountNonMonthlyDonors);
        $entity->setTotalDelegates($totalDelegates);
        $entity->setTotalActiveSchools($totalActiveSchools);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }
}

<?php

namespace App\Command\Transaction;

use App\Entity\UserDonor;
use App\Service\CreateTransactionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

#[AsCommand(
    name: 'app:transaction:create',
    description: 'Create transaction for donors',
)]
class CreateCommand extends Command
{
    private int $userDonorLastId = 0;

    public function __construct(private EntityManagerInterface $entityManager, private CreateTransactionService $createTransactionService)
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

        if ($this->createTransactionService->isHoliday()) {
            $io->success('Today is holiday and we will not create and send transactions');

            return Command::SUCCESS;
        }

        while (true) {
            $userDonors = $this->getUserDonors();
            if (empty($userDonors)) {
                break;
            }

            foreach ($userDonors as $userDonor) {
                $output->write('Process donor '.$userDonor->getUser()->getEmail().' at '.date('Y-m-d H:i:s'));
                if ($this->createTransactionService->hasNotPaidTransactionsInLastDays($userDonor, 10)) {
                    $output->writeln(' | has "not paid" transactions in last 10 days');
                    continue;
                }

                $sumTransactions = $this->createTransactionService->getSumTransactions($userDonor);
                $donorRemainingAmount = $userDonor->getAmount() - $sumTransactions;

                $transactionCreated = $this->createTransactionService->create($userDonor, $donorRemainingAmount);
                $output->writeln(' | Is transaction created: '.($transactionCreated ? 'Yes' : 'No'));

                if ($transactionCreated) {
                    $this->createTransactionService->sendNewTransactionEmail($userDonor);
                }
            }

            $this->entityManager->clear();
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function getUserDonors(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('ud')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->andWhere('ud.id > :lastId')
            ->setParameter('lastId', $this->userDonorLastId)
            ->orderBy('ud.id', 'ASC')
            ->setMaxResults(100);

        $results = $qb->getQuery()->getResult();
        if (!empty($results)) {
            $last = end($results);
            $this->userDonorLastId = $last->getId();
        }

        return $results;
    }
}

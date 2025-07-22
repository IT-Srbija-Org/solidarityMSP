<?php

namespace App\Command;

use App\Repository\DamagedEducatorRepository;
use App\Repository\TransactionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

#[AsCommand(
    name: 'app:cache-numbers',
    description: 'Cache numbers for public display',
)]
class CacheNumbersCommand extends Command
{
    public function __construct(private TransactionRepository $transactionRepository, private DamagedEducatorRepository $damagedEducatorRepository)
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

        $this->transactionRepository->getSumConfirmedAmount(false);
        $this->damagedEducatorRepository->getMissingSumAmount(false);
        $this->damagedEducatorRepository->getTotals(false);
        $this->transactionRepository->getTotalActiveDonors(false);
        $this->transactionRepository->getSchoolWithConfirmedTransactions(false);

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }
}

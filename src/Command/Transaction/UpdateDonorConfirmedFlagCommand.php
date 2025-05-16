<?php

namespace App\Command\Transaction;

use App\Entity\LogEntityChange;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

#[AsCommand(
    name: 'app:transaction:update-donor-confirmed-flag',
    description: 'Update donor confirmed flag',
)]
class UpdateDonorConfirmedFlagCommand extends Command
{
    private int $lastId = 0;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = new FlockStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock($this->getName(), 0);
        if (!$lock->acquire()) {
            return Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $io->section('Command started at '.date('Y-m-d H:i:s'));

        while (true) {
            $transactions = $this->getTransactions();
            if (empty($transactions)) {
                break;
            }

            foreach ($transactions as $transaction) {
                $io->comment('Transaction ID: '.$transaction->getId());

                $log = $this->entityManager->getRepository(LogEntityChange::class)->findOneBy([
                    'entityId' => $transaction->getId(),
                    'entityName' => Transaction::class,
                    'changedByUser' => $transaction->getUser(),
                ], ['id' => 'DESC']);

                if (empty($log)) {
                    continue;
                }

                $changes = $log->getChanges();
                if (empty($changes['status'])) {
                    continue;
                }

                if (Transaction::STATUS_WAITING_CONFIRMATION == $changes['status'][1]) {
                    $transaction->setUserDonorConfirmed(true);
                    $this->entityManager->persist($transaction);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function getTransactions(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('t')
            ->from(Transaction::class, 't')
            ->andWhere('t.id > :lastId')
            ->setParameter('lastId', $this->lastId)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(100);

        $results = $qb->getQuery()->getResult();
        if (!empty($results)) {
            $last = end($results);
            $this->lastId = $last->getId();
        }

        return $results;
    }
}

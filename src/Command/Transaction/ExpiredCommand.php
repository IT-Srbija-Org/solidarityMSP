<?php

namespace App\Command\Transaction;

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
    name: 'app:transaction:expired',
    description: 'Transaction automatically expired after 72 hours',
)]
class ExpiredCommand extends Command
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

        // Cancelled comment
        $comment = 'Instruckija za uplatu je automatski istekla jer je prošlo više od 72 sata.';

        while (true) {
            $transactions = $this->getTransactions();
            if (empty($transactions)) {
                break;
            }

            foreach ($transactions as $transaction) {
                $io->comment('Transaction ID: '.$transaction->getId());
                $status = Transaction::STATUS_EXPIRED;

                $user = $transaction->getUser();
                if (!$user->getLastVisit() || $user->getLastVisit() < $transaction->getCreatedAt()) {
                    $status = Transaction::STATUS_NOT_PAID;
                }

                $transaction->setStatus($status);
                $transaction->setStatusComment($comment);
                $this->entityManager->persist($transaction);
            }

            $this->entityManager->flush();
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function getTransactions(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('t')
            ->from(Transaction::class, 't')
            ->where('t.status = :status')
            ->setParameter('status', Transaction::STATUS_NEW)
            ->andWhere('t.createdAt < :createdAt')
            ->setParameter('createdAt', new \DateTimeImmutable('-72 hours'))
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

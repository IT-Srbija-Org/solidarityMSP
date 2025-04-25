<?php

namespace App\Command;

use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cancelled-transaction',
    description: 'Cancelled transaction after 72h',
)]
class CancelledTransactionCommand extends Command
{
    private int $lastId = 0;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Command started at '.date('Y-m-d H:i:s'));

        // Cancelled comment
        $comment = 'Instruckija za uplatu je automatski otkazana jer je prošlo više od 72 sata.';

        while (true) {
            $items = $this->getItems();
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $io->comment('Cancelled transaction ID: '.$item->getId());

                $item->setStatus(Transaction::STATUS_CANCELLED);
                $item->setStatusComment($comment);
                $this->entityManager->persist($item);
            }

            $this->entityManager->flush();
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function getItems(): array
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

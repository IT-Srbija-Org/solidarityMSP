<?php

namespace App\Repository;

use App\Entity\DamagedEducatorPeriod;
use App\Entity\School;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<School>
 */
class SchoolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private TransactionRepository $transactionRepository, private DamagedEducatorRepository $damagedEducatorRepository)
    {
        parent::__construct($registry, School::class);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('c');

        if (!empty($criteria['name'])) {
            $qb->andWhere('c.name LIKE :name')
                ->setParameter('name', '%'.$criteria['name'].'%');
        }

        if (!empty($criteria['city'])) {
            $qb->andWhere('c.city = :city')
                ->setParameter('city', $criteria['city']);
        }

        if (!empty($criteria['type'])) {
            $qb->andWhere('c.type = :type')
                ->setParameter('type', $criteria['type']);
        }

        if (isset($criteria['processing'])) {
            $qb->andWhere('c.processing = :processing')
                ->setParameter('processing', $criteria['processing']);
        }

        if (isset($criteria['havePayoutPriority'])) {
            $qb->andWhere('c.havePayoutPriority = :havePayoutPriority')
                ->setParameter('havePayoutPriority', $criteria['havePayoutPriority']);
        }

        // Set the sorting
        $qb->orderBy('c.id', 'ASC');

        // Apply pagination only if $limit is set and greater than 0
        if ($limit && $limit > 0) {
            $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
        }

        // Get the query
        $query = $qb->getQuery();

        // Create the paginator if pagination is applied
        if ($limit && $limit > 0) {
            $paginator = new Paginator($query, true);

            return [
                'items' => iterator_to_array($paginator),
                'total' => count($paginator),
                'current_page' => $page,
                'total_pages' => ceil(count($paginator) / $limit),
            ];
        }

        return [
            'items' => $query->getResult(),
            'total' => count($query->getResult()),
            'current_page' => 1,
            'total_pages' => 1,
        ];
    }

    public function getStatistics(DamagedEducatorPeriod $period, School $school): array
    {
        $sumAmountNewTransactions = $this->transactionRepository->getSumAmountTransactions($period, $school, [Transaction::STATUS_NEW]);
        $sumAmountWaitingConfirmationTransactions = $this->transactionRepository->getSumAmountTransactions($period, $school, [Transaction::STATUS_WAITING_CONFIRMATION, Transaction::STATUS_EXPIRED]);
        $sumAmountConfirmedTransactions = $this->transactionRepository->getSumAmountTransactions($period, $school, [Transaction::STATUS_CONFIRMED]);
        $totalDamagedEducators = $this->damagedEducatorRepository->getTotalsByPeriod($period, $school);

        $averageAmountPerDamagedEducator = 0;
        if ($sumAmountConfirmedTransactions > 0 && $totalDamagedEducators > 0) {
            $averageAmountPerDamagedEducator = floor($sumAmountConfirmedTransactions / $totalDamagedEducators);
        }

        return [
            'schoolEntity' => $school,
            'periodEntity' => $period,
            'totalDamagedEducators' => $totalDamagedEducators,
            'sumAmount' => $this->damagedEducatorRepository->getSumAmountByPeriod($period, $school),
            'sumAmountNewTransactions' => $sumAmountNewTransactions,
            'sumAmountWaitingConfirmationTransactions' => $sumAmountWaitingConfirmationTransactions,
            'sumAmountConfirmedTransactions' => $sumAmountConfirmedTransactions,
            'averageAmountPerDamagedEducator' => $averageAmountPerDamagedEducator,
        ];
    }
}

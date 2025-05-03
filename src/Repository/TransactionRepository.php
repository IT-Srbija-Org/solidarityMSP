<?php

namespace App\Repository;

use App\Entity\DamagedEducatorPeriod;
use App\Entity\School;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->leftJoin('t.damagedEducator', 'e')
            ->leftJoin('e.school', 's');

        if (isset($criteria['id'])) {
            $qb->andWhere('t.id = :id')
                ->setParameter('id', $criteria['id']);
        }

        if (isset($criteria['user'])) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $criteria['user']);
        }

        if (isset($criteria['period'])) {
            $qb->andWhere('e.period = :period')
                ->setParameter('period', $criteria['period']);
        }

        if (!empty($criteria['donor'])) {
            $qb->leftJoin('t.user', 'u')
                ->andWhere('u.email LIKE :donor')
                ->setParameter('donor', '%'.$criteria['donor'].'%');
        }

        if (!empty($criteria['educator'])) {
            $qb->andWhere('e.name LIKE :educator')
                ->setParameter('educator', '%'.$criteria['educator'].'%');
        }

        if (!empty($criteria['school'])) {
            $qb->andWhere('e.school = :school')
                ->setParameter('school', $criteria['school']);
        }

        if (!empty($criteria['city'])) {
            $qb->andWhere('s.city = :city')
                ->setParameter('city', $criteria['city']);
        }

        if (!empty($criteria['accountNumber'])) {
            $criteria['accountNumber'] = str_replace('-', '', $criteria['accountNumber']);

            $qb->andWhere('t.accountNumber LIKE :accountNumber')
                ->setParameter('accountNumber', '%'.$criteria['accountNumber'].'%');
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        // Set the sorting
        $qb->orderBy('t.id', 'DESC');

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

    public function getSumAmountConfirmedTransactions(DamagedEducatorPeriod $period, ?School $school): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb = $qb->select('SUM(t.amount)')
            ->from(Transaction::class, 't')
            ->innerJoin('t.damagedEducator', 'de')
            ->andWhere('de.period = :period')
            ->setParameter('period', $period)
            ->andWhere('t.status = :status')
            ->setParameter('status', Transaction::STATUS_CONFIRMED);

        if ($school) {
            $qb->andWhere('de.school = :school')
                ->setParameter('school', $school);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

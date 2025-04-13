<?php

namespace App\Repository;

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

        if (!empty($criteria['search'])) {
            $or = $qb->expr()->orX();
            $or->add($qb->expr()->like('u.email', ':search'));
            $or->add($qb->expr()->like('e.name', ':search'));
            $or->add($qb->expr()->like('s.name', ':search'));
            $or->add($qb->expr()->like('c.name', ':search'));
            $qb->leftJoin('t.educator', 'e')
               ->leftJoin('t.user', 'u')
               ->leftJoin('e.school', 's')
               ->leftJoin('s.city', 'c')
               ->where($or)
               ->setParameter('search', '%'.$criteria['search'].'%');
        }

        if (isset($criteria['status'])) {
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
}

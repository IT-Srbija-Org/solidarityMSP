<?php

namespace App\Repository;

use App\Entity\DamagedEducator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DamagedEducator>
 */
class DamagedEducatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DamagedEducator::class);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('e');
        $qb->leftJoin('e.school', 's');

        if (isset($criteria['period'])) {
            $qb->andWhere('e.period = :period')
                ->setParameter('period', $criteria['period']);
        }

        if (!empty($criteria['name'])) {
            $qb->andWhere('e.name LIKE :name')
                ->setParameter('name', '%'.$criteria['name'].'%');
        }

        if (!empty($criteria['city'])) {
            $qb->andWhere('s.city = :city')
                ->setParameter('city', $criteria['city']);
        }

        if (!empty($criteria['school'])) {
            $qb->andWhere('e.school = :school')
                ->setParameter('school', $criteria['school']);
        }

        if (isset($criteria['schools'])) {
            $qb->andWhere('e.school IN (:schools)')
                ->setParameter('schools', $criteria['schools']);
        }

        if (!empty($criteria['accountNumber'])) {
            $qb->andWhere('e.accountNumber LIKE :accountNumber')
                ->setParameter('accountNumber', '%'.$criteria['accountNumber'].'%');
        }

        if (!empty($criteria['createdBy'])) {
            $qb->andWhere('e.createdBy = :createdBy')
                ->setParameter('createdBy', $criteria['createdBy']);
        }

        // Set the sorting
        $qb->orderBy('e.id', 'DESC');

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

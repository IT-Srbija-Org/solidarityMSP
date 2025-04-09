<?php

namespace App\Repository;

use App\Entity\Educator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Educator>
 */
class EducatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Educator::class);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('e');

        if (!empty($criteria['name'])) {
            $qb->andWhere('e.name LIKE :name')
                ->setParameter('name', '%'.$criteria['name'].'%');
        }

        if (isset($criteria['school'])) {
            $qb->andWhere('e.school = :school')
                ->setParameter('school', $criteria['school']);
        }

        if (isset($criteria['schools'])) {
            $qb->andWhere('e.school IN (:schools)')
                ->setParameter('schools', $criteria['schools']);
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

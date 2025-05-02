<?php

namespace App\Repository;

use App\Entity\School;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<School>
 */
class SchoolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, School::class);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50, string $sort = 'id', string $direction = 'ASC'): array
    {
        $allowedSorts = ['id', 'name', 'cityName', 'typeName', 'createdAt'];
        $allowedDirections = ['ASC', 'DESC'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        if (!in_array(strtoupper($direction), $allowedDirections, true)) {
            $direction = 'ASC';
        }

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

        // Set the sorting
        switch ($sort) {
            case 'cityName':
                $qb->leftJoin('c.city', 'city');
                $qb->orderBy('city.name', $direction);
                break;
            case 'typeName':
                $qb->leftJoin('c.type', 'type');
                $qb->orderBy('type.name', $direction);
                break;
            default:
                $qb->orderBy('c.'.$sort, $direction);
        }

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

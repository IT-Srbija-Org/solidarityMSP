<?php

namespace App\Repository;

use App\Entity\UserDonor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserDonor>
 */
class UserDonorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDonor::class);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('ud');

        if (isset($criteria['isMonthly'])) {
            $qb->andWhere('ud.isMonthly = :isMonthly')
                ->setParameter('isMonthly', $criteria['isMonthly']);
        }

        if (!empty($criteria['firstName'])) {
            $qb->innerJoin('ud.user', 'u')
                ->andWhere('u.firstName LIKE :firstName')
                ->setParameter('firstName', '%'.$criteria['firstName'].'%');
        }

        if (!empty($criteria['lastName'])) {
            $qb->innerJoin('ud.user', 'u')
                ->andWhere('u.lastName LIKE :lastName')
                ->setParameter('lastName', '%'.$criteria['lastName'].'%');
        }

        if (!empty($criteria['email'])) {
            $qb->innerJoin('ud.user', 'u')
                ->andWhere('u.email LIKE :email')
                ->setParameter('email', '%'.$criteria['email'].'%');
        }

        // Set the sorting
        $qb->orderBy('ud.id', 'DESC');

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

<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('u');

        if (!empty($criteria['firstName'])) {
            $qb->andWhere('u.firstName LIKE :firstName')
                ->setParameter('firstName', '%'.$criteria['firstName'].'%');
        }

        if (!empty($criteria['lastName'])) {
            $qb->andWhere('u.lastName LIKE :lastName')
                ->setParameter('lastName', '%'.$criteria['lastName'].'%');
        }

        if (!empty($criteria['email'])) {
            $qb->andWhere('u.email LIKE :email')
                ->setParameter('email', '%'.$criteria['email'].'%');
        }

        if (!empty($criteria['role']) && 'ROLE_USER' != $criteria['role']) {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%'.$criteria['role'].'%');
        }

        if (isset($criteria['isActive'])) {
            $qb->andWhere('u.isActive = :isActive')
                ->setParameter('isActive', $criteria['isActive']);
        }

        if (isset($criteria['isVerified'])) {
            $qb->andWhere('u.isVerified = :isVerified')
                ->setParameter('isVerified', $criteria['isVerified']);
        }

        // Set the sorting
        $qb->orderBy('u.id', 'ASC');

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

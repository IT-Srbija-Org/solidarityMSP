<?php

namespace App\Repository;

use App\Entity\UserDelegateSchool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserDelegateSchool>
 */
class UserDelegateSchoolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDelegateSchool::class);
    }

    public function getTotalActiveSchools(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return (int) $qb->select('COUNT(DISTINCT uds.school)')
            ->from(UserDelegateSchool::class, 'uds')
            ->getQuery()
            ->getSingleScalarResult();
    }
}

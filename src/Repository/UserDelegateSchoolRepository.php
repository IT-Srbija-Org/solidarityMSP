<?php

namespace App\Repository;

use App\Entity\DamagedEducatorPeriod;
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

    public function getTotalActiveSchools(?DamagedEducatorPeriod $period): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb = $qb->select('COUNT(DISTINCT uds.school)')
            ->from(UserDelegateSchool::class, 'uds');

        if ($period) {
            $qb->innerJoin('uds.school', 's')
                ->innerJoin('s.damagedEducators', 'dep')
                ->andWhere('dep.period = :period')
                ->setParameter('period', $period);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

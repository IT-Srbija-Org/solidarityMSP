<?php

namespace App\Repository;

use App\Entity\DamagedEducatorPeriod;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DamagedEducatorPeriod>
 */
class DamagedEducatorPeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DamagedEducatorPeriod::class);
    }

    public function allowToAdd(User $user, ?DamagedEducatorPeriod $period): bool
    {
        $tmpAllowUserIds = [18075, 18017, 18007, 18244, 18116, 18241, 18064, 18051, 18364, 18064, 18017, 18110, 18279];

        $currentDate = new \DateTime();
        if ('2025-05-07' == $currentDate->format('Y-m-d') && in_array($user->getId(), $tmpAllowUserIds)) {
            return true;
        }

        return $period->allowToAdd();
    }
}

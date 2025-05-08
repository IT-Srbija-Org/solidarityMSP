<?php

namespace App\Repository;

use App\Entity\DamagedEducatorPeriod;
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
}

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
}

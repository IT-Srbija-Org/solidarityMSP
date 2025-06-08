<?php

namespace App\Repository;

use App\Entity\LogCommandChange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogCommandChange>
 */
class LogCommandChangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogCommandChange::class);
    }

    public function save(string $name, string $entityName, int $entityId, string $message): void
    {
        $entity = new LogCommandChange();
        $entity->setName($name);
        $entity->setEntityName($entityName);
        $entity->setEntityId($entityId);
        $entity->setMessage($message);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
}

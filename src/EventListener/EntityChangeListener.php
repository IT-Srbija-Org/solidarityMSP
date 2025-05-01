<?php

namespace App\EventListener;

use App\Entity\LogEntityChange;
use App\Entity\LogLogin;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::onFlush, priority: 500)]
#[AsDoctrineListener(event: Events::postFlush, priority: 500)]
class EntityChangeListener
{
    private const ACTION_CREATE = 'create';
    private const ACTION_UPDATE = 'update';
    private const ACTION_DELETE = 'delete';

    private array $pendingCreates = [];

    public function __construct(private readonly Security $security)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->logEntityChange($em, $uow, $entity, self::ACTION_UPDATE);
        }

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->logEntityChange($em, $uow, $entity, self::ACTION_CREATE);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->logEntityChange($em, $uow, $entity, self::ACTION_DELETE);
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (empty($this->pendingCreates)) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($this->pendingCreates as $hash => $logEntityChange) {
            foreach ($uow->getIdentityMap() as $entities) {
                foreach ($entities as $entity) {
                    if (spl_object_hash($entity) !== $hash) {
                        continue;
                    }

                    if (method_exists($entity, 'getId') && null === $entity->getId()) {
                        continue;
                    }

                    $logEntityChange->setEntityId($entity->getId());
                    $em->persist($logEntityChange);
                    break 2;
                }
            }
        }

        $this->pendingCreates = [];
        $em->flush();
    }

    private function logEntityChange(EntityManagerInterface $em, UnitOfWork $uow, object $entity, string $action): void
    {
        if ($entity instanceof LogEntityChange) {
            return;
        }

        if ($entity instanceof LogLogin) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();
        if (empty($user)) {
            return;
        }

        $changeSet = $uow->getEntityChangeSet($entity);
        foreach ($changeSet as $field => [$oldValue, $newValue]) {
            $changeSet[$field][0] = $this->transformValue($oldValue);
            $changeSet[$field][1] = $this->transformValue($newValue);
        }

        unset($changeSet['lastVisit']);
        if (empty($changeSet)) {
            return;
        }

        $log = new LogEntityChange();
        $log->setAction($action);
        $log->setEntityName(get_class($entity));
        $log->setEntityId(method_exists($entity, 'getId') ? $entity->getId() : null);
        $log->setChanges($changeSet);
        $log->setChangedByUser($user);

        $em->persist($log);
        $uow->computeChangeSet($em->getClassMetadata(LogEntityChange::class), $log);

        if (self::ACTION_CREATE === $action) {
            $this->pendingCreates[spl_object_hash($entity)] = $log;
        }
    }

    private function transformValue(mixed $data): mixed
    {
        if (is_object($data) && method_exists($data, 'getId')) {
            return $data->getId();
        }

        return $data;
    }
}

<?php

namespace App\Repository;

use App\Entity\DamagedEducator;
use App\Entity\DamagedEducatorPeriod;
use App\Entity\School;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private CacheInterface $cache)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->leftJoin('t.damagedEducator', 'e')
            ->leftJoin('e.school', 's');

        if (isset($criteria['id'])) {
            $qb->andWhere('t.id = :id')
                ->setParameter('id', $criteria['id']);
        }

        if (isset($criteria['user'])) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $criteria['user']);
        }

        if (isset($criteria['period'])) {
            $qb->andWhere('e.period = :period')
                ->setParameter('period', $criteria['period']);
        }

        if (!empty($criteria['donor'])) {
            $qb->leftJoin('t.user', 'u')
                ->andWhere('u.email LIKE :donor')
                ->setParameter('donor', '%'.$criteria['donor'].'%');
        }

        if (!empty($criteria['educator'])) {
            $qb->andWhere('e.name LIKE :educator')
                ->setParameter('educator', '%'.$criteria['educator'].'%');
        }

        if (!empty($criteria['school'])) {
            $qb->andWhere('e.school = :school')
                ->setParameter('school', $criteria['school']);
        }

        if (!empty($criteria['schools'])) {
            $qb->andWhere('e.school IN (:schools)')
                ->setParameter('schools', $criteria['schools']);
        }

        if (!empty($criteria['city'])) {
            $qb->andWhere('s.city = :city')
                ->setParameter('city', $criteria['city']);
        }

        if (!empty($criteria['accountNumber'])) {
            $criteria['accountNumber'] = str_replace('-', '', $criteria['accountNumber']);

            $qb->andWhere('t.accountNumber LIKE :accountNumber')
                ->setParameter('accountNumber', '%'.$criteria['accountNumber'].'%');
        }

        if (isset($criteria['isUserDonorConfirmed'])) {
            $qb->andWhere('t.userDonorConfirmed = :isUserDonorConfirmed')
                ->setParameter('isUserDonorConfirmed', $criteria['isUserDonorConfirmed']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        // Set the sorting
        $qb->orderBy('t.id', 'DESC');

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

    public function getSumAmountTransactions(DamagedEducatorPeriod $period, ?School $school, array $statuses): int
    {
        $qb = $this->createQueryBuilder('t');
        $qb = $qb->select('SUM(t.amount)')
            ->innerJoin('t.damagedEducator', 'de')
            ->andWhere('de.period = :period')
            ->setParameter('period', $period)
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('statuses', $statuses);

        if ($school) {
            $qb->andWhere('de.school = :school')
                ->setParameter('school', $school);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function cancelAllTransactions(DamagedEducator $damagedEducator, string $comment, array $statuses, bool $checkDonorLastVisit): void
    {
        $transactions = $this->findBy([
            'damagedEducator' => $damagedEducator,
            'status' => $statuses,
        ]);

        foreach ($transactions as $transaction) {
            if ($checkDonorLastVisit) {
                $user = $transaction->getUser();
                if ($user->getLastVisit() && $user->getLastVisit() > $transaction->getCreatedAt()) {
                    continue;
                }
            }

            $transaction->setStatus(Transaction::STATUS_CANCELLED);
            $transaction->setStatusComment($comment);
        }

        $this->getEntityManager()->flush();
    }

    public function getSumAmountForAccountNumber(int $period, string $accountNumber, array $statuses): int
    {
        $qb = $this->createQueryBuilder('t');
        $qb = $qb->select('SUM(t.amount)')
            ->innerJoin('t.damagedEducator', 'de')
            ->andWhere('de.period = :period')
            ->setParameter('period', $period)
            ->andWhere('t.accountNumber = :accountNumber')
            ->setParameter('accountNumber', $accountNumber)
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('statuses', $statuses);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getSumConfirmedAmountForActiveDamagedEducators(): int
    {
        $qb = $this->createQueryBuilder('t');
        $qb = $qb->select('SUM(t.amount)')
            ->innerJoin('t.damagedEducator', 'de')
            ->andWhere('de.status = :status1')
            ->setParameter('status1', DamagedEducator::STATUS_NEW)
            ->andWhere('t.status = :status2')
            ->setParameter('status2', Transaction::STATUS_CONFIRMED);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getSumConfirmedAmount(bool $useCache): int
    {
        return $this->cache->get('transaction-getSumConfirmedAmount', function (ItemInterface $item) {
            $item->expiresAfter(86400);

            $qb = $this->createQueryBuilder('t');
            $qb = $qb->select('SUM(t.amount)')
                ->andWhere('t.status = :status')
                ->setParameter('status', Transaction::STATUS_CONFIRMED);

            return (int) $qb->getQuery()->getSingleScalarResult();
        }, $useCache ? 1.0 : INF);
    }

    public function getSchoolWithConfirmedTransactions(bool $useCache): array
    {
        return $this->cache->get('transaction-getSchoolWithConfirmedTransactions', function (ItemInterface $item) {
            $item->expiresAfter(86400);

            $qb = $this->createQueryBuilder('t');
            $qb = $qb->select('s.name, c.name AS cityName, SUM(t.amount) AS totalConfirmedAmount, COUNT(DISTINCT t.accountNumber) AS totalDamagedEducators')
                ->innerJoin('t.damagedEducator', 'de')
                ->innerJoin('de.school', 's')
                ->innerJoin('s.city', 'c')
                ->andWhere('t.status = :status')
                ->setParameter('status', Transaction::STATUS_CONFIRMED)
                ->groupBy('s.id')
                ->orderBy('c.name', 'ASC');

            return $qb->getQuery()->getResult();
        }, $useCache ? 1.0 : INF);
    }

    public function getTotalActiveDonors(bool $useCache): int
    {
        return $this->cache->get('transaction-getTotalActiveDonors', function (ItemInterface $item) {
            $item->expiresAfter(86400);

            $qb = $this->createQueryBuilder('t');
            $qb = $qb->select('COUNT(DISTINCT t.user)')
                ->andWhere('t.status = :status')
                ->setParameter('status', Transaction::STATUS_CONFIRMED);

            return (int) $qb->getQuery()->getSingleScalarResult();
        }, $useCache ? 1.0 : INF);
    }

    public function getPendingTransactions(DamagedEducatorPeriod $period, array $schools): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb = $qb->select('t')
            ->innerJoin('t.damagedEducator', 'de')
            ->andWhere('t.status IN (:status)')
            ->setParameter('status', [
                Transaction::STATUS_WAITING_CONFIRMATION,
                Transaction::STATUS_EXPIRED,
            ])
            ->andWhere('de.period = :period')
            ->setParameter('period', $period)
            ->andWhere('de.school IN (:schools)')
            ->setParameter('schools', $schools)
            ->addOrderBy('de.id', 'ASC');

        $transactions = $qb->getQuery()->getResult();
        foreach ($transactions as $key => $transaction) {
            if (!$transaction->allowToChangeStatus()) {
                unset($transactions[$key]);
            }
        }

        return $transactions;
    }
}

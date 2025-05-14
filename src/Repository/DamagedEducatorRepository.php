<?php

namespace App\Repository;

use App\Entity\DamagedEducator;
use App\Entity\DamagedEducatorPeriod;
use App\Entity\School;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<DamagedEducator>
 */
class DamagedEducatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private CacheInterface $cache, private TransactionRepository $transactionRepository)
    {
        parent::__construct($registry, DamagedEducator::class);
    }

    public function getFromUser(User $user): array
    {
        $userDelegateSchools = $user->getUserDelegateSchools();

        $schoolIds = [];
        foreach ($userDelegateSchools as $userDelegateSchool) {
            $schoolIds[] = $userDelegateSchool->getSchool()->getId();
        }

        return $this->findBy([
            'school' => $schoolIds,
        ]);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('e');
        $qb->leftJoin('e.school', 's');

        if (isset($criteria['period'])) {
            $qb->andWhere('e.period = :period')
                ->setParameter('period', $criteria['period']);
        }

        if (!empty($criteria['name'])) {
            $qb->andWhere('e.name LIKE :name')
                ->setParameter('name', '%'.$criteria['name'].'%');
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['city'])) {
            $qb->andWhere('s.city = :city')
                ->setParameter('city', $criteria['city']);
        }

        if (!empty($criteria['school'])) {
            $qb->andWhere('e.school = :school')
                ->setParameter('school', $criteria['school']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        if (isset($criteria['schools'])) {
            $qb->andWhere('e.school IN (:schools)')
                ->setParameter('schools', $criteria['schools']);
        }

        if (!empty($criteria['accountNumber'])) {
            $criteria['accountNumber'] = str_replace('-', '', $criteria['accountNumber']);

            $qb->andWhere('e.accountNumber LIKE :accountNumber')
                ->setParameter('accountNumber', '%'.$criteria['accountNumber'].'%');
        }

        if (!empty($criteria['createdBy'])) {
            $qb->andWhere('e.createdBy = :createdBy')
                ->setParameter('createdBy', $criteria['createdBy']);
        }

        // Set the sorting
        $qb->orderBy('e.id', 'DESC');

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

    public function getSumAmountByPeriod(DamagedEducatorPeriod $period, ?School $school): int
    {
        $qb = $this->createQueryBuilder('e');
        $qb = $qb->select('SUM(e.amount)')
            ->andWhere('e.period = :period')
            ->setParameter('period', $period);

        if ($school) {
            $qb->andWhere('e.school = :school')
                ->setParameter('school', $school);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getSumAmount(bool $useCache): int
    {
        return $this->cache->get('damaged-educator-getSumAmount', function (ItemInterface $item) {
            $item->expiresAfter(86400);

            $qb = $this->createQueryBuilder('e');
            $qb = $qb->select('SUM(e.amount)');

            return (int) $qb->getQuery()->getSingleScalarResult();
        }, $useCache ? 1.0 : INF);
    }

    public function getTotals(bool $useCache): int
    {
        return $this->cache->get('damaged-educator-getTotals', function (ItemInterface $item) {
            $item->expiresAfter(86400);

            $qb = $this->createQueryBuilder('e');
            $qb = $qb->select('COUNT(DISTINCT e.accountNumber)');

            return (int) $qb->getQuery()->getSingleScalarResult();
        }, $useCache ? 1.0 : INF);
    }

    public function getTotalsByPeriod(DamagedEducatorPeriod $period, ?School $school): int
    {
        $qb = $this->createQueryBuilder('de');
        $qb = $qb->select('COUNT(DISTINCT de.accountNumber)')
            ->andWhere('de.period = :period')
            ->setParameter('period', $period);

        if ($school) {
            $qb->andWhere('de.school = :school')
                ->setParameter('school', $school);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getTotalsSchoolByPeriod(DamagedEducatorPeriod $period): int
    {
        $qb = $this->createQueryBuilder('e');
        $qb = $qb->select('COUNT(DISTINCT e.school)')
            ->andWhere('e.period = :period')
            ->setParameter('period', $period);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getOnlyByRemainingAmount(int $maxDonationAmount, int $minTransactionDonationAmount, array $parameters): array
    {
        $transactionStatuses = [
            Transaction::STATUS_NEW,
            Transaction::STATUS_WAITING_CONFIRMATION,
            Transaction::STATUS_CONFIRMED,
            Transaction::STATUS_EXPIRED,
        ];

        $queryString = '';
        $queryString .= 'WHERE de.status = :status';

        $queryParameters = [];
        $queryParameters['status'] = DamagedEducator::STATUS_NEW;

        if (!empty($parameters['schoolTypeId'])) {
            $queryString .= ' AND st.id = :schoolTypeId';
            $queryParameters['schoolTypeId'] = $parameters['schoolTypeId'];
        }

        if (!empty($parameters['schoolId'])) {
            $queryString .= ' AND de.school_id = :schoolId';
            $queryParameters['schoolId'] = $parameters['schoolId'];
        }

        $stmt = $this->getEntityManager()->getConnection()->executeQuery('
            SELECT de.id, de.period_id, de.account_number, de.amount
            FROM damaged_educator AS de
             INNER JOIN damaged_educator_period AS dep ON dep.id = de.period_id AND dep.processing = 1
             INNER JOIN school AS s ON s.id = de.school_id
             INNER JOIN school_type AS st ON st.id = s.type_id
             '.$queryString.'
            ', $queryParameters);

        $items = [];
        foreach ($stmt->fetchAllAssociative() as $item) {
            if ($item['amount'] > $maxDonationAmount) {
                $item['amount'] = $maxDonationAmount;
            }

            $transactionSum = $this->transactionRepository->getSumAmountForAccountNumber($item['period_id'], $item['account_number'], $transactionStatuses);
            $item['remainingAmount'] = $item['amount'] - $transactionSum;
            if ($item['remainingAmount'] < $minTransactionDonationAmount) {
                continue;
            }

            $items[$item['id']] = $item;
        }

        // Sort by remaining amount
        uasort($items, function ($a, $b) {
            return $b['remainingAmount'] <=> $a['remainingAmount'];
        });

        return $items;
    }
}

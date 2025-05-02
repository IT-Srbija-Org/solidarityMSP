<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50, string $sort = 'id', string $direction = 'DESC'): array
    {
        $allowedSorts = ['id', 'period', 'donor', 'damagedEducator', 'accountNumber', 'amount', 'createdAt', 'status'];
        $allowedDirections = ['ASC', 'DESC'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        if (!in_array(strtoupper($direction), $allowedDirections, true)) {
            $direction = 'DESC';
        }

        $qb = $this->createQueryBuilder('t');
        $qb->leftJoin('t.damagedEducator', 'e')
            ->leftJoin('e.school', 's');

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

        if (!empty($criteria['city'])) {
            $qb->andWhere('s.city = :city')
                ->setParameter('city', $criteria['city']);
        }

        if (!empty($criteria['accountNumber'])) {
            $criteria['accountNumber'] = str_replace('-', '', $criteria['accountNumber']);

            $qb->andWhere('t.accountNumber LIKE :accountNumber')
                ->setParameter('accountNumber', '%'.$criteria['accountNumber'].'%');
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        // Set the sorting
        switch ($sort) {
            case 'period':
                $qb->leftJoin('e.period', 'p');
                $qb->addOrderBy('p.year', $direction)
                   ->addOrderBy('p.month', $direction)
                   ->addOrderBy('p.type', $direction);
                break;
            case 'donor':
                $qb->leftJoin('t.user', 'ud');
                $qb->addSelect('CONCAT(ud.firstName, \' \', ud.lastName) AS HIDDEN fullName')
                   ->orderBy('fullName', $direction);
                break;
            case 'damagedEducator':
                $qb->orderBy('e.name', $direction);
                break;
            default:
                $qb->orderBy('t.'.$sort, $direction);
        }

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

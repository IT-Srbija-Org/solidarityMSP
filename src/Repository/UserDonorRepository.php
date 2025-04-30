<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserDonor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @extends ServiceEntityRepository<UserDonor>
 */
class UserDonorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private MailerInterface $mailer)
    {
        parent::__construct($registry, UserDonor::class);
    }

    public function sendSuccessEmail(User $user): void
    {
        $message = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Potvrda registracije donora na Mrežu solidarnosti')
            ->htmlTemplate('donor/request/success_email.html.twig');

        $this->mailer->send($message);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50, string $sort = 'id', string $direction = 'DESC'): array
    {
        $allowedSorts = ['id', 'fullName'];
        $allowedDirections = ['ASC', 'DESC'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        if (!in_array(strtoupper($direction), $allowedDirections, true)) {
            $direction = 'ASC';
        }

        $qb = $this->createQueryBuilder('ud');
        $qb->innerJoin('ud.user', 'u')
            ->andWhere('u.isActive = 1');

        if (isset($criteria['isMonthly'])) {
            $qb->andWhere('ud.isMonthly = :isMonthly')
                ->setParameter('isMonthly', $criteria['isMonthly']);
        }

        if (!empty($criteria['firstName'])) {
            $qb->andWhere('u.firstName LIKE :firstName')
                ->setParameter('firstName', '%'.$criteria['firstName'].'%');
        }

        if (!empty($criteria['lastName'])) {
            $qb->andWhere('u.lastName LIKE :lastName')
                ->setParameter('lastName', '%'.$criteria['lastName'].'%');
        }

        if (!empty($criteria['email'])) {
            $qb->andWhere('u.email LIKE :email')
                ->setParameter('email', '%'.$criteria['email'].'%');
        }

        // Set the sorting
        switch ($sort) {
            case 'fullName':
                $qb->addSelect('CONCAT(u.firstName, \' \', u.lastName) AS HIDDEN fullName')
                   ->orderBy('fullName', $direction);
                break;
            default:
                $qb->orderBy('u.'.$sort, $direction);
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
                'total_pages' => (int) ceil(count($paginator) / $limit),
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

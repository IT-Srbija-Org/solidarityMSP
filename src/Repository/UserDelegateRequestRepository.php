<?php

namespace App\Repository;

use App\Entity\UserDelegateRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @extends ServiceEntityRepository<UserDelegateRequest>
 */
class UserDelegateRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private MailerInterface $mailer)
    {
        parent::__construct($registry, UserDelegateRequest::class);
    }

    public function sendConfirmationEmail(UserDelegateRequest $userDelegateRequest): void
    {
        $message = (new TemplatedEmail())
            ->to($userDelegateRequest->getUser()->getEmail())
            ->subject('Vaš zahtev za delegata je odobren')
            ->htmlTemplate('admin/userDelegateRequest/confirmation_email.html.twig')
            ->context(['userDelegateRequest' => $userDelegateRequest]);

        $this->mailer->send($message);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('udr');
        $qb->innerJoin('udr.user', 'u')
            ->andWhere('u.isActive = 1');

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

        if (!empty($criteria['phone'])) {
            $qb->andWhere('udr.phone LIKE :phone')
                ->setParameter('phone', '%'.$criteria['phone'].'%');
        }

        if (!empty($criteria['city'])) {
            $qb->andWhere('udr.city = :city')
                ->setParameter('city', $criteria['city']);
        }

        if (!empty($criteria['school'])) {
            $qb->andWhere('udr.school = :school')
                ->setParameter('school', $criteria['school']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('udr.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        // Set the sorting
        $qb->orderBy('udr.id', 'DESC');

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

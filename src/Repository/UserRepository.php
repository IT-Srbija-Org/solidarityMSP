<?php

namespace App\Repository;

use App\Entity\User;
use App\Security\EmailVerifier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private LoginLinkHandlerInterface $loginLinkHandler, private EmailVerifier $emailVerifier, private MailerInterface $mailer)
    {
        parent::__construct($registry, User::class);
    }

    public function createUser(?string $firstName, ?string $lastName, string $email): User
    {
        $user = $this->findOneBy(['email' => $email]);
        if ($user) {
            return $user;
        }

        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $user;
    }

    public function sendVerificationLink(User $user, ?string $action): void
    {
        $this->emailVerifier->sendEmailConfirmation('verify_email', $user, $action,
            (new TemplatedEmail())
                ->to($user->getEmail())
                ->subject('Link za verifikaciju email adrese')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }

    public function sendLoginLink(User $user): void
    {
        $loginLinkDetails = $this->loginLinkHandler->createLoginLink($user);
        $loginLink = $loginLinkDetails->getUrl();

        $message = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Link za logovanje')
            ->htmlTemplate('security/login_link_email.html.twig')
            ->context(['link' => $loginLink]);

        $this->mailer->send($message);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('u');

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

        if (!empty($criteria['role']) && 'ROLE_USER' != $criteria['role']) {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%'.$criteria['role'].'%');
        }

        if (isset($criteria['isActive'])) {
            $qb->andWhere('u.isActive = :isActive')
                ->setParameter('isActive', $criteria['isActive']);
        }

        if (isset($criteria['isEmailVerified'])) {
            $qb->andWhere('u.isEmailVerified = :isEmailVerified')
                ->setParameter('isEmailVerified', $criteria['isEmailVerified']);
        }

        $hasSchool = isset($criteria['school']);
        $hasCity = isset($criteria['city']);

        if ($hasSchool || $hasCity) {
            $qb->join('u.userDelegateSchools', 'uds')
                ->join('uds.school', 's');

            if ($hasSchool) {
                $qb->andWhere('s.id = :school')
                    ->setParameter(':school', $criteria['school']);
            }

            if ($hasCity) {
                $qb->join('s.city', 'c')
                    ->andWhere('c.id = :city')
                    ->setParameter(':city', $criteria['city']);
            }
        }

        // Set the sorting
        $qb->orderBy('u.id', 'ASC');

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

<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\UserDonor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * @extends ServiceEntityRepository<UserDonor>
 */
class UserDonorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private MailerInterface $mailer)
    {
        parent::__construct($registry, UserDonor::class);
    }

    public function hasNotPaidTransactionsInLastDays(UserDonor $userDonor, int $days): bool
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(t.id)')
            ->from(Transaction::class, 't')
            ->where('t.user = :user')
            ->andWhere('t.status = :status')
            ->andWhere('t.createdAt > :dateLimit')
            ->setParameter('user', $userDonor->getUser())
            ->setParameter('status', Transaction::STATUS_NOT_PAID)
            ->setParameter('dateLimit', new \DateTime('-'.$days.' days'));

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function getSumTransactions(UserDonor $userDonor): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('SUM(t.amount)')
            ->from(Transaction::class, 't')
            ->where('t.user = :user')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('user', $userDonor->getUser())
            ->setParameter('statuses', [
                Transaction::STATUS_NEW,
                Transaction::STATUS_WAITING_CONFIRMATION,
                Transaction::STATUS_CONFIRMED,
                Transaction::STATUS_EXPIRED,
            ]);

        if ($userDonor->isMonthly()) {
            $qb->andWhere('t.createdAt > :dateLimit')
                ->setParameter('dateLimit', new \DateTime('-30 days'));
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function sumTransactionsToEducator(UserDonor $userDonor, string $accountNumber): int
    {
        $transactionStatuses = [
            Transaction::STATUS_NEW,
            Transaction::STATUS_WAITING_CONFIRMATION,
            Transaction::STATUS_CONFIRMED,
            Transaction::STATUS_EXPIRED,
        ];

        $stmt = $this->getEntityManager()->getConnection()->executeQuery('
            SELECT SUM(t.amount)
            FROM transaction AS t
            WHERE t.user_id = :userId
             AND t.account_number = :accountNumber
             AND t.status IN ('.implode(',', $transactionStatuses).')
             AND t.created_at > DATE(NOW() - INTERVAL 1 YEAR)
            ', [
            'userId' => $userDonor->getUser()->getId(),
            'accountNumber' => $accountNumber,
        ]);

        return (int) $stmt->fetchOne();
    }

    public function sendNewTransactionEmail(UserDonor $userDonor): void
    {
        $message = (new TemplatedEmail())
            ->to($userDonor->getUser()->getEmail())
            ->from(new Address('donatori@mrezasolidarnosti.org', 'Mreža Solidarnosti'))
            ->subject('Stigle su ti nove instrukcije za uplatu')
            ->htmlTemplate('email/donor-new-transactions.html.twig')
            ->context([
                'user' => $userDonor->getUser(),
            ]);

        try {
            $this->mailer->send($message);
        } catch (\Exception $exception) {
        }
    }

    public function sendSuccessEmail(User $user): void
    {
        $message = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Potvrda registracije donora na Mrežu solidarnosti')
            ->htmlTemplate('donor/request/success_email.html.twig');

        $this->mailer->send($message);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
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
        $qb->orderBy('ud.id', 'DESC');

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

    public function unsubscribe(UserDonor $userDonor): void
    {
        $transactions = $this->getEntityManager()->getRepository(Transaction::class)->findBy([
            'user' => $userDonor->getUser(),
            'status' => Transaction::STATUS_NEW,
        ]);

        foreach ($transactions as $transaction) {
            $transaction->setStatus(Transaction::STATUS_CANCELLED);
            $transaction->setStatusComment('Instruckija za uplatu je automatski otkazana pošto se donator odjavio sa liste donatora.');
            $this->getEntityManager()->persist($transaction);
        }

        $this->getEntityManager()->remove($userDonor);
        $this->getEntityManager()->flush();
    }

    public function getTotal(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return (int) $qb->select('COUNT(ud.id)')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalMonthly(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return (int) $qb->select('COUNT(ud.id)')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('ud.isMonthly = 1')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalNonMonthly(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return (int) $qb->select('COUNT(ud.id)')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('ud.isMonthly = 0')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumAmountMonthlyDonors(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return (int) $qb->select('SUM(ud.amount)')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('ud.isMonthly = 1')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumAmountNonMonthlyDonors(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return (int) $qb->select('SUM(ud.amount)')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('ud.isMonthly = 0')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->getQuery()
            ->getSingleScalarResult();
    }
}

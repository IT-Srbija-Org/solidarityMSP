<?php

namespace App\Command\Transaction;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\UserDonor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:transaction:notify-donors',
    description: 'Notify donors 1 day before transaction expiration',
)]
class NotifyDonorsCommand extends Command
{
    private int $lastId = 0;

    public function __construct(private EntityManagerInterface $entityManager, private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = new FlockStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock($this->getName(), 0);
        if (!$lock->acquire()) {
            return Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $io->section('Command started at '.date('Y-m-d H:i:s'));

        while (true) {
            $donors = $this->getDonors();
            if (empty($donors)) {
                break;
            }

            foreach ($donors as $donor) {
                if (!$this->hasTransactionsFromYesterday($donor)) {
                    continue;
                }

                $output->writeln('Send email to '.$donor->getUser()->getEmail());
                $this->sendEmail($donor->getUser());
            }
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function hasTransactionsFromYesterday(UserDonor $userDonor): bool
    {
        $stmt = $this->entityManager->getConnection()->executeQuery('
            SELECT COUNT(*)
            FROM transaction AS t
            WHERE t.user_id = :userId
             AND t.status = :status
             AND DATE(t.created_at) = DATE(NOW() - INTERVAL 1 DAY)
            ', [
            'userId' => $userDonor->getUser()->getId(),
            'status' => Transaction::STATUS_NEW,
        ]);

        return (bool) $stmt->fetchOne();
    }

    public function sendEmail(User $user): void
    {
        $message = (new TemplatedEmail())
            ->to($user->getEmail())
            ->from(new Address('donatori@mrezasolidarnosti.org', 'Mreža Solidarnosti'))
            ->subject('Podsetnik: Instrukcije za uplatu donacije uskoro ističu')
            ->htmlTemplate('email/transaction-notify-donor.html.twig')
            ->context([
                'user' => $user,
            ]);

        try {
            $this->mailer->send($message);
        } catch (\Exception $exception) {
        }
    }

    public function getDonors(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('ud')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->andWhere('ud.id > :lastId')
            ->setParameter('lastId', $this->lastId)
            ->orderBy('ud.id', 'ASC')
            ->setMaxResults(100);

        $results = $qb->getQuery()->getResult();
        if (!empty($results)) {
            $last = end($results);
            $this->lastId = $last->getId();
        }

        return $results;
    }
}

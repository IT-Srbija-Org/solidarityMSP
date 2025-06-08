<?php

namespace App\Command;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\UserDonor;
use App\Repository\LogCommandChangeRepository;
use App\Repository\TransactionRepository;
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
    name: 'app:inactive-donors',
    description: 'Remove inactive donors',
)]
class InactiveDonorsCommand extends Command
{
    private int $lastId = 0;

    public function __construct(private EntityManagerInterface $entityManager, private MailerInterface $mailer, private TransactionRepository $transactionRepository, private LogCommandChangeRepository $logCommandChangeRepository)
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
            $items = $this->getUserDonors();
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $userDonor = $this->entityManager->getRepository(UserDonor::class)->find($item['id']);
                if ($userDonor->getCreatedAt() > new \DateTime('-15 days')) {
                    continue;
                }

                if (!$this->haveNotPaidLastTransactions($userDonor)) {
                    continue;
                }

                $output->writeln('Remove user donor and send email to '.$item['email']);

                // Remove user donor
                $this->entityManager->remove($userDonor);
                $this->entityManager->flush();

                // Log changes
                $this->logCommandChangeRepository->save($this->getName(), User::class, $item['user_id'], 'Automatska odjava sa liste donatora zbog neaktivnosti');

                // Send email
                $this->sendEmail($item['email']);
            }
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function haveNotPaidLastTransactions(UserDonor $userDonor): bool
    {
        $transactions = $this->transactionRepository->findBy(['user' => $userDonor->getUser()], ['id' => 'DESC']);
        $dates = [];

        foreach ($transactions as $transaction) {
            if (Transaction::STATUS_CANCELLED == $transaction->getStatus()) {
                continue;
            }

            $notPaid = (Transaction::STATUS_NOT_PAID == $transaction->getStatus() && !$transaction->isUserDonorConfirmed());
            if (!$notPaid) {
                return false;
            }

            $dates[$transaction->getCreatedAt()->format('Y-m-d')] = true;
            if (3 == count($dates)) {
                return true;
            }
        }

        return false;
    }

    public function sendEmail(string $email): void
    {
        $message = (new TemplatedEmail())
            ->to($email)
            ->from(new Address('donatori@mrezasolidarnosti.org', 'Mreža Solidarnosti'))
            ->subject('Automatska odjava sa liste donatora zbog neaktivnosti')
            ->htmlTemplate('email/inactive-donor.html.twig');

        try {
            $this->mailer->send($message);
        } catch (\Exception $exception) {
        }
    }

    public function getUserDonors(): array
    {
        $stmt = $this->entityManager->getConnection()->executeQuery('
            SELECT ud.id, u.id AS user_id, u.email
            FROM user_donor AS ud
             INNER JOIN user AS u ON u.id = ud.user_id AND u.is_active = 1 AND u.is_email_verified = 1
            WHERE ud.id > :lastId
            ORDER BY ud.id ASC
            LIMIT 100
            ', ['lastId' => $this->lastId]);

        $results = $stmt->fetchAllAssociative();
        if (!empty($results)) {
            $last = end($results);
            $this->lastId = $last['id'];
        }

        return $results;
    }
}

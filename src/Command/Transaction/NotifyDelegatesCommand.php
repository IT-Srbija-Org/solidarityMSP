<?php

namespace App\Command\Transaction;

use App\Entity\DamagedEducator;
use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\UserDelegateSchool;
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
    name: 'app:transaction:notify-delegates',
    description: 'Notify delegates when transactions require verification',
)]
class NotifyDelegatesCommand extends Command
{
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

        $delegates = $this->getDelegates();
        foreach ($delegates as $delegate) {
            $schools = $this->entityManager->getRepository(UserDelegateSchool::class)->findBy([
                'user' => $delegate,
            ]);

            $havePendingTransactions = false;
            foreach ($schools as $school) {
                $damagedEducators = $this->entityManager->getRepository(DamagedEducator::class)->findBy([
                    'school' => $school->getSchool(),
                ]);

                foreach ($damagedEducators as $damagedEducator) {
                    $transactions = $this->entityManager->getRepository(Transaction::class)->findBy([
                        'damagedEducator' => $damagedEducator,
                        'status' => [
                            Transaction::STATUS_WAITING_CONFIRMATION,
                            Transaction::STATUS_EXPIRED,
                        ],
                    ]);

                    foreach ($transactions as $transaction) {
                        if ($transaction->allowToChangeStatus()) {
                            $havePendingTransactions = true;
                            break 3;
                        }
                    }
                }
            }

            if (empty($havePendingTransactions)) {
                continue;
            }

            $output->writeln('Send email to '.$delegate->getEmail());
            $this->sendEmail($delegate);
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function sendEmail(User $user): void
    {
        $message = (new TemplatedEmail())
            ->to($user->getEmail())
            ->from(new Address('delegati@mrezasolidarnosti.org', 'Mreža Solidarnosti'))
            ->subject('Postoje instrukcije za uplatu koje treba potvrditi')
            ->htmlTemplate('email/transaction-notify-delegate.html.twig');

        try {
            $this->mailer->send($message);
        } catch (\Exception $exception) {
        }
    }

    public function getDelegates(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('u')
            ->from(User::class, 'u')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DELEGATE%')
            ->orderBy('u.id', 'ASC');

        return $qb->getQuery()->getResult();
    }
}

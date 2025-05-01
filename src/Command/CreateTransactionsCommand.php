<?php

namespace App\Command;

use App\Entity\DamagedEducator;
use App\Entity\DamagedEducatorPeriod;
use App\Entity\Transaction;
use App\Entity\UserDonor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:create-transactions',
    description: 'Create transaction for donors',
)]
class CreateTransactionsCommand extends Command
{
    private int $minTransactionDonationAmount = 500;
    private $maxDonationAmount;
    private int $maxYearDonationAmount = 30000;
    private int $userDonorLastId = 0;
    private array $damagedEducators = [];

    public function __construct(private EntityManagerInterface $entityManager, private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('maxDonationAmount', InputArgument::REQUIRED, 'Maximum amount that the donor will send to the damaged educator');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Command started at '.date('Y-m-d H:i:s'));

        $store = new FlockStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock($this->getName(), 0);
        if (!$lock->acquire()) {
            return Command::FAILURE;
        }

        $this->maxDonationAmount = (int) $input->getArgument('maxDonationAmount');
        if ($this->maxDonationAmount < $this->minTransactionDonationAmount) {
            $io->error('Maximum donation amount must be greater than '.$this->minTransactionDonationAmount);

            return Command::FAILURE;
        }

        if ($this->maxDonationAmount > 100000) {
            $io->error('Maximum donation amount must be less than 100000');

            return Command::FAILURE;
        }

        // Get damaged educators
        $this->damagedEducators = $this->getDamagedEducators();

        while (true) {
            $userDonors = $this->getUserDonors();
            if (empty($userDonors)) {
                break;
            }

            foreach ($userDonors as $userDonor) {
                if (empty($this->damagedEducators)) {
                    $output->writeln('No damaged educators found');
                    break 2;
                }

                $output->write('Process donor '.$userDonor->getUser()->getEmail().' at '.date('Y-m-d H:i:s'));

                $sumTransactions = $this->getSumTransactions($userDonor);
                $donorRemainingAmount = $userDonor->getAmount() - $sumTransactions;
                if ($donorRemainingAmount < $this->minTransactionDonationAmount) {
                    $output->writeln(' | remaining amount is less than '.$this->minTransactionDonationAmount);
                    continue;
                }

                $totalTransactions = 0;
                foreach ($this->damagedEducators as $damagedEducator) {
                    $sumTransactionAmount = $this->sumTransactionsToEducator($userDonor, $damagedEducator['account_number']);
                    if ($sumTransactionAmount >= $this->maxYearDonationAmount) {
                        continue;
                    }

                    $totalTransactions += $this->createTransactions($userDonor, $donorRemainingAmount, $damagedEducator['id']);

                    if ($donorRemainingAmount < $this->minTransactionDonationAmount) {
                        break;
                    }
                }

                $output->writeln(' | Total transaction created: '.$totalTransactions);

                if ($totalTransactions > 0) {
                    $this->sendEmail($userDonor);
                }
            }

            $this->entityManager->clear();
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function sendEmail(UserDonor $userDonor): void
    {
        $message = (new TemplatedEmail())
            ->to($userDonor->getUser()->getEmail())
            ->subject('Stigle su vam nove instrukcije za uplatu')
            ->htmlTemplate('email/donor-new-transactions.html.twig')
            ->context([
                'user' => $userDonor->getUser(),
            ]);

        try {
            $this->mailer->send($message);
        } catch (\Exception $exception) {
        }
    }

    public function createTransactions(UserDonor $userDonor, int &$donorRemainingAmount, int $damagedEducatorId): int
    {
        $totalCreated = 0;
        while ($donorRemainingAmount >= $this->minTransactionDonationAmount) {
            $damagedEducator = $this->damagedEducators[$damagedEducatorId];

            $amount = $damagedEducator['remainingAmount'];
            if ($amount < $this->minTransactionDonationAmount) {
                // All transaction created for this educator
                unset($this->damagedEducators[$damagedEducatorId]);
                break;
            }

            if ($amount > $donorRemainingAmount) {
                $amount = $donorRemainingAmount;
            }

            if ($amount >= $this->maxYearDonationAmount) {
                $amount = $this->maxYearDonationAmount;
            }

            $transaction = new Transaction();
            $transaction->setUser($userDonor->getUser());

            $entity = $this->entityManager->getRepository(DamagedEducator::class)->find($damagedEducator['id']);
            $transaction->setDamagedEducator($entity);
            $transaction->setAccountNumber($damagedEducator['account_number']);

            $transaction->setAmount($amount);
            $donorRemainingAmount -= $transaction->getAmount();
            $this->damagedEducators[$damagedEducator['id']]['remainingAmount'] -= $transaction->getAmount();

            $this->entityManager->persist($transaction);
            ++$totalCreated;

            if ($amount >= $this->maxYearDonationAmount) {
                break;
            }
        }

        $this->entityManager->flush();

        return $totalCreated;
    }

    public function sumTransactionsToEducator(UserDonor $userDonor, string $accountNumber): int
    {
        $transactionStatuses = [
            Transaction::STATUS_NEW,
            Transaction::STATUS_WAITING_CONFIRMATION,
            Transaction::STATUS_CONFIRMED,
        ];

        $stmt = $this->entityManager->getConnection()->executeQuery('
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

    public function getDamagedEducators(): array
    {
        $transactionStatuses = [
            Transaction::STATUS_NEW,
            Transaction::STATUS_WAITING_CONFIRMATION,
            Transaction::STATUS_CONFIRMED,
        ];

        $stmt = $this->entityManager->getConnection()->executeQuery('
            SELECT de.id, de.period_id, de.account_number, de.amount,
             COALESCE(
              (SELECT SUM(amount)
               FROM transaction
               WHERE damaged_educator_id = de.id
                AND status IN ('.implode(',', $transactionStatuses).')),
              0) AS transactionSum
            FROM damaged_educator AS de
             INNER JOIN damaged_educator_period AS dep ON dep.id = de.period_id
            WHERE dep.active = 0
             AND de.status = :status
            HAVING transactionSum < de.amount
            ORDER BY de.id ASC
            ', [
            'status' => DamagedEducator::STATUS_NEW,
        ]);

        // Temporary ignore first half of the February 2025
        $period = $this->entityManager->getRepository(DamagedEducatorPeriod::class)->findOneBy([
            'month' => 2,
            'year' => 2025,
            'type' => DamagedEducatorPeriod::TYPE_FIRST_HALF,
        ]);

        $items = [];
        foreach ($stmt->fetchAllAssociative() as $item) {
            if ($period && $period->getId() == $item['period_id']) {
                continue;
            }

            if ($item['amount'] > $this->maxDonationAmount) {
                $item['amount'] = $this->maxDonationAmount;
            }

            $item['remainingAmount'] = $item['amount'] - $item['transactionSum'];
            if ($item['remainingAmount'] < $this->minTransactionDonationAmount) {
                continue;
            }

            unset($item['transactionSum']);
            $items[$item['id']] = $item;
        }

        // Sort by remaining amount
        uasort($items, function ($a, $b) {
            return $b['remainingAmount'] <=> $a['remainingAmount'];
        });

        return $items;
    }

    public function getSumTransactions(UserDonor $userDonor): int
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('SUM(t.amount)')
            ->from(Transaction::class, 't')
            ->where('t.user = :user')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('user', $userDonor->getUser())
            ->setParameter('statuses', [
                Transaction::STATUS_NEW,
                Transaction::STATUS_WAITING_CONFIRMATION,
                Transaction::STATUS_CONFIRMED,
            ]);

        if ($userDonor->isMonthly()) {
            $qb->andWhere('t.createdAt > :dateLimit')
                ->setParameter('dateLimit', new \DateTime('-30 days'));
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getUserDonors(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('ud')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->andWhere('ud.id > :lastId')
            ->setParameter('lastId', $this->userDonorLastId)
            ->orderBy('ud.id', 'ASC')
            ->setMaxResults(100);

        $results = $qb->getQuery()->getResult();
        if (!empty($results)) {
            $last = end($results);
            $this->userDonorLastId = $last->getId();
        }

        return $results;
    }
}

<?php

namespace App\Command\Transaction;

use App\Entity\DamagedEducator;
use App\Entity\Transaction;
use App\Entity\UserDonor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

#[AsCommand(
    name: 'app:transaction:create-for-large-amount',
    description: 'Create transaction for donors who donated large amount',
)]
class CreateForLargeAmountCommand extends Command
{
    private int $minTransactionDonationAmount = 10000;
    private int $maxTransactionDonationAmount = 60000;
    private int $maxDonationAmount = 60000;
    private int $maxYearDonationAmount = 80000;
    private array $damagedEducators = [];

    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Command started at ' . date('Y-m-d H:i:s'));

        $store = new FlockStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock($this->getName(), 0);
        if (!$lock->acquire()) {
            return Command::FAILURE;
        }

        // Get user donor repository
        $userDonorRepository = $this->entityManager->getRepository(UserDonor::class);

        // Get damaged educators
        $this->damagedEducators = $this->getDamagedEducators();

        // Get donors
        $userDonors = $this->getUserDonors();
        if (empty($userDonors)) {
            $output->writeln('No damaged educators found');
        }

        foreach ($userDonors as $userDonor) {
            $output->write('Process donor ' . $userDonor->getUser()->getEmail() . ' at ' . date('Y-m-d H:i:s'));
            $output->write(' | Amount: ' . $userDonor->getAmount());

            $sumTransactions = $userDonorRepository->getSumTransactions($userDonor);
            $donorRemainingAmount = $userDonor->getAmount() - $sumTransactions;
            if ($donorRemainingAmount < $this->minTransactionDonationAmount) {
                $output->writeln(' | remaining amount is less than ' . $this->minTransactionDonationAmount);
                continue;
            }

            // Set max transaction donation amount for donor
            $this->setMaxTransactionDonationAmount($userDonor);

            $totalTransactions = 0;
            foreach ($this->damagedEducators as $damagedEducator) {
                $sumTransactionAmount = $userDonorRepository->sumTransactionsToEducator($userDonor, $damagedEducator['account_number']);
                $sumTransactionAmount += $this->maxTransactionDonationAmount;
                if ($sumTransactionAmount >= $this->maxYearDonationAmount) {
                    continue;
                }

                $totalTransactions += $this->createTransaction($userDonor, $donorRemainingAmount, $damagedEducator['id']);

                if ($donorRemainingAmount < $this->minTransactionDonationAmount) {
                    break;
                }
            }

            $output->writeln(' | Total transaction created: ' . $totalTransactions);

            if ($totalTransactions > 0) {
                $userDonorRepository->sendNewTransactionEmail($userDonor);
            }
        }

        $io->success('Command finished at ' . date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function setMaxTransactionDonationAmount(UserDonor $userDonor): void
    {
        if ($userDonor->getAmount() <= 120000) {
            $this->maxTransactionDonationAmount = 25000;
            return;
        }

        if ($userDonor->getAmount() <= 200000) {
            $this->maxTransactionDonationAmount = 35000;
            return;
        }

        if ($userDonor->getAmount() <= 300000) {
            $this->maxTransactionDonationAmount = 45000;
            return;
        }

        $this->maxTransactionDonationAmount = $this->maxDonationAmount;
    }

    public function createTransaction(UserDonor $userDonor, int &$donorRemainingAmount, int $damagedEducatorId): int
    {
        $damagedEducator = $this->damagedEducators[$damagedEducatorId];
        $amount = $damagedEducator['remainingAmount'];
        if ($amount < $this->minTransactionDonationAmount) {
            // All transaction created for this educator
            unset($this->damagedEducators[$damagedEducatorId]);
            return 0;
        }

        if ($amount > $donorRemainingAmount) {
            $amount = $donorRemainingAmount;
        }

        if ($amount > $this->maxTransactionDonationAmount) {
            $amount = $this->maxTransactionDonationAmount;
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
        $this->entityManager->flush();

        return 1;
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
                AND status IN (' . implode(',', $transactionStatuses) . ')),
              0) AS transactionSum
            FROM damaged_educator AS de
             INNER JOIN damaged_educator_period AS dep ON dep.id = de.period_id
            WHERE dep.active = 1
             AND de.status = :status
            HAVING transactionSum < de.amount
            ORDER BY de.id ASC
            ', [
            'status' => DamagedEducator::STATUS_NEW,
        ]);

        $items = [];
        foreach ($stmt->fetchAllAssociative() as $item) {
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

    public function getUserDonors(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('ud')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->andWhere('ud.amount >= 100000')
            ->orderBy('ud.amount', 'DESC');

        return $qb->getQuery()->getResult();
    }
}

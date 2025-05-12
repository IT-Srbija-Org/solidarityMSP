<?php

namespace App\Command\Transaction;

use App\Entity\DamagedEducator;
use App\Entity\Transaction;
use App\Entity\UserDonor;
use App\Repository\DamagedEducatorRepository;
use App\Repository\UserDonorRepository;
use App\Service\HelperService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

#[AsCommand(
    name: 'app:transaction:create',
    description: 'Create transaction for donors',
)]
class CreateCommand extends Command
{
    private int $minTransactionDonationAmount = 500;
    private $maxDonationAmount;
    private int $maxYearDonationAmount = 30000;
    private int $userDonorLastId = 0;
    private array $damagedEducators = [];

    public function __construct(private EntityManagerInterface $entityManager, private HelperService $helperService, private UserDonorRepository $userDonorRepository, private DamagedEducatorRepository $damagedEducatorRepository)
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

        if ($this->helperService->isHoliday()) {
            $io->success('Today is holiday and we will not create and send transactions');

            return Command::SUCCESS;
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
        $this->damagedEducators = $this->damagedEducatorRepository->getOnlyByRemainingAmount($this->maxDonationAmount, $this->minTransactionDonationAmount);

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
                if ($this->userDonorRepository->hasNotPaidTransactionsInLastDays($userDonor, 10)) {
                    $output->writeln(' | has not paid transactions in last 10 days');
                    continue;
                }

                $sumTransactions = $this->userDonorRepository->getSumTransactions($userDonor);
                $donorRemainingAmount = $userDonor->getAmount() - $sumTransactions;
                if ($donorRemainingAmount < $this->minTransactionDonationAmount) {
                    $output->writeln(' | remaining amount is less than '.$this->minTransactionDonationAmount);
                    continue;
                }

                $totalTransactions = 0;
                foreach ($this->damagedEducators as $damagedEducator) {
                    $sumTransactionAmount = $this->userDonorRepository->sumTransactionsToEducator($userDonor, $damagedEducator['account_number']);
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
                    $this->userDonorRepository->sendNewTransactionEmail($userDonor);
                }
            }

            $this->entityManager->clear();
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
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

    public function getUserDonors(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('ud')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->andWhere('ud.amount < 100000')
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

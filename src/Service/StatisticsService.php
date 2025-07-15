<?php

namespace App\Service;

use App\Repository\DamagedEducatorRepository;
use App\Repository\TransactionRepository;

class StatisticsService
{
    public function __construct(private TransactionRepository $transactionRepository, private DamagedEducatorRepository $damagedEducatorRepository)
    {
    }

    public function getGeneralNumbers(): array
    {
        $transactionSumConfirmedAmount = $this->transactionRepository->getSumConfirmedAmount(false);
        $damagedEducatorSumAmount = $this->damagedEducatorRepository->getSumAmount(false);
        $totalDamagedEducators = $this->damagedEducatorRepository->getTotals(false);
        $totalActiveDonors = $this->transactionRepository->getTotalActiveDonors(false);

        $avgConfirmedAmountPerEducator = 0;
        if ($transactionSumConfirmedAmount > 0 && $totalDamagedEducators > 0) {
            $avgConfirmedAmountPerEducator = ceil($transactionSumConfirmedAmount / $totalDamagedEducators);
        }

        $avgInputAmountPerEducator = 0;
        if ($damagedEducatorSumAmount > 0 && $totalDamagedEducators > 0) {
            $avgInputAmountPerEducator = ceil($damagedEducatorSumAmount / $totalDamagedEducators);
        }

        return [
            'transactionSumConfirmedAmount' => $transactionSumConfirmedAmount,
            'damagedEducatorSumAmount' => $damagedEducatorSumAmount,
            'totalDamagedEducators' => $totalDamagedEducators,
            'totalActiveDonors' => $totalActiveDonors,
            'avgConfirmedAmountPerEducator' => $avgConfirmedAmountPerEducator,
            'avgInputAmountPerEducator' => $avgInputAmountPerEducator,
        ];
    }
}

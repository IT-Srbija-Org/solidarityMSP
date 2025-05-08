<?php

namespace App\Controller;

use App\Repository\DamagedEducatorRepository;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'static_')]
class StaticController extends AbstractController
{
    public function __construct(private TransactionRepository $transactionRepository, private DamagedEducatorRepository $damagedEducatorRepository)
    {
    }

    #[Route('/statusi-kod-ostecenih-prosvetnih-radnika', name: 'damaged_educator_status')]
    public function damagedEducatorStatus(): Response
    {
        return $this->render('static/damaged_educator_status.html.twig');
    }

    #[Route('/statusi-kod-instrukcija-za-uplatu', name: 'transaction_status')]
    public function transactionStatus(): Response
    {
        return $this->render('static/transaction_status.html.twig');
    }

    #[Route('/umrezeno-po-skolama', name: 'schools')]
    public function schools(): Response
    {
        $transactionSumConfirmedAmount = $this->transactionRepository->getSumConfirmedAmount(true);
        $damagedEducatorSumAmount = $this->damagedEducatorRepository->getSumAmount(true);
        $totalDamagedEducators = $this->damagedEducatorRepository->getTotals(true);
        $totalActiveDonors = $this->transactionRepository->getTotalActiveDonors(true);

        $avgConfirmedAmountPerEducator = 0;
        if ($transactionSumConfirmedAmount > 0 && $totalDamagedEducators > 0) {
            $avgConfirmedAmountPerEducator = $transactionSumConfirmedAmount / $totalDamagedEducators;
        }

        $avgInputAmountPerEducator = 0;
        if ($damagedEducatorSumAmount > 0 && $totalDamagedEducators > 0) {
            $avgInputAmountPerEducator = $damagedEducatorSumAmount / $totalDamagedEducators;
        }

        return $this->render('static/schools.html.twig', [
            'transactionSumConfirmedAmount' => $transactionSumConfirmedAmount,
            'damagedEducatorSumAmount' => $damagedEducatorSumAmount,
            'totalDamagedEducators' => $totalDamagedEducators,
            'totalActiveDonors' => $totalActiveDonors,
            'avgConfirmedAmountPerEducator' => $avgConfirmedAmountPerEducator,
            'avgInputAmountPerEducator' => $avgInputAmountPerEducator,
            'schools' => $this->transactionRepository->getSchoolWithConfirmedTransactions(true),
        ]);
    }
}

<?php

namespace App\Controller;

use App\Repository\DamagedEducatorRepository;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(private TransactionRepository $transactionRepository, private DamagedEducatorRepository $damagedEducatorRepository)
    {
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    public function basicNumbers(): Response
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

        return $this->render('home/basic_numbers.html.twig', [
            'transactionSumConfirmedAmount' => $transactionSumConfirmedAmount,
            'damagedEducatorSumAmount' => $damagedEducatorSumAmount,
            'totalDamagedEducators' => $totalDamagedEducators,
            'totalActiveDonors' => $totalActiveDonors,
            'avgConfirmedAmountPerEducator' => $avgConfirmedAmountPerEducator,
            'avgInputAmountPerEducator' => $avgInputAmountPerEducator,
        ]);
    }
}

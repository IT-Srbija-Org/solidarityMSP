<?php

namespace App\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(private StatisticsService $statisticsService)
    {
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    public function generalNumbers(): Response
    {
        $generalNumbers = $this->statisticsService->getGeneralNumbers();

        return $this->render('home/basic_numbers.html.twig', [
            'transactionSumConfirmedAmount' => $generalNumbers['transactionSumConfirmedAmount'],
            'damagedEducatorMissingSumAmount' => $generalNumbers['damagedEducatorMissingSumAmount'],
            'damagedEducatorSumAmount' => $generalNumbers['damagedEducatorSumAmount'],
            'totalDamagedEducators' => $generalNumbers['totalDamagedEducators'],
            'totalActiveDonors' => $generalNumbers['totalActiveDonors'],
            'avgConfirmedAmountPerEducator' => $generalNumbers['avgConfirmedAmountPerEducator'],
        ]);
    }
}

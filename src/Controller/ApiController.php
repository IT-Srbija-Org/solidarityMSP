<?php

namespace App\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    public function __construct(private StatisticsService $statisticsService)
    {
    }

    #[Route('/numbers', name: 'numbers')]
    public function generalNumbers(): JsonResponse
    {
        $generalNumbers = $this->statisticsService->getGeneralNumbers();

        return $this->json([
            'totalConfirmed' => $generalNumbers['transactionSumConfirmedAmount'],
            'totalRequired' => $generalNumbers['damagedEducatorSumAmount'],
            'totalEducators' => $generalNumbers['totalDamagedEducators'],
            'totalActiveDonors' => $generalNumbers['totalActiveDonors'],
            'avgConfirmedAmountPerEducator' => $generalNumbers['avgConfirmedAmountPerEducator'],
            'avgRequiredAmountPerEducator' => $generalNumbers['avgInputAmountPerEducator'],
        ]);
    }
}

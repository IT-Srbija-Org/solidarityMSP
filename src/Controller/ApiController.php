<?php

namespace App\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    public function __construct(private StatisticsService $statisticsService)
    {
    }

    #[Route('/v1/numbers')]
    public function v1Numbers(): JsonResponse
    {
        $generalNumbers = $this->statisticsService->getGeneralNumbers();

        return $this->json([
            'totalConfirmedAmount' => $generalNumbers['transactionSumConfirmedAmount'],
            'totalRequiredAmount' => $generalNumbers['damagedEducatorSumAmount'],
            'totalEducators' => $generalNumbers['totalDamagedEducators'],
            'totalActiveDonors' => $generalNumbers['totalActiveDonors'],
            'avgConfirmedAmountPerEducator' => $generalNumbers['avgConfirmedAmountPerEducator'],
            'avgRequiredAmountPerEducator' => 0,
        ]);
    }

    #[Route('/v2/numbers')]
    public function v2Numbers(): JsonResponse
    {
        $generalNumbers = $this->statisticsService->getGeneralNumbers();

        return $this->json([
            'transactionSumConfirmedAmount' => $generalNumbers['transactionSumConfirmedAmount'],
            'damagedEducatorMissingSumAmount' => $generalNumbers['damagedEducatorMissingSumAmount'],
            'damagedEducatorSumAmount' => $generalNumbers['damagedEducatorSumAmount'],
            'totalDamagedEducators' => $generalNumbers['totalDamagedEducators'],
            'totalActiveDonors' => $generalNumbers['totalActiveDonors'],
            'avgConfirmedAmountPerEducator' => $generalNumbers['avgConfirmedAmountPerEducator'],
        ]);
    }
}

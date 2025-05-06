<?php

namespace App\Controller\Admin;

use App\Entity\DamagedEducator;
use App\Entity\DamagedEducatorPeriod;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Repository\UserDelegateSchoolRepository;
use App\Repository\UserDonorRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(EntityManagerInterface $entityManager, UserDonorRepository $userDonorRepository, UserRepository $userRepository, UserDelegateSchoolRepository $userDelegateSchoolRepository, TransactionRepository $transactionRepository): Response
    {
        $totalDonors = $userDonorRepository->getTotal();
        $totalMonthlyDonors = $userDonorRepository->getTotalMonthly();
        $totalNonMonthlyDonors = $userDonorRepository->getTotalNonMonthly();
        $sumAmountMonthlyDonors = $userDonorRepository->sumAmountMonthlyDonors();
        $sumAmountNonMonthlyDonors = $userDonorRepository->sumAmountNonMonthlyDonors();
        $totalDelegates = $userRepository->getTotalDelegates();
        $totalActiveSchools = $userDelegateSchoolRepository->getTotalActiveSchools(null);
        $totalAdmins = $userRepository->getTotalAdmins();

        $period = $entityManager->getRepository(DamagedEducatorPeriod::class)->findAll();
        $periodItems = [];

        foreach ($period as $pData) {
            $qb = $entityManager->createQueryBuilder();
            $sumAmountDamagedEducators = $qb->select('SUM(de.amount)')
                ->from(DamagedEducator::class, 'de')
                ->andWhere('de.period = :period')
                ->setParameter('period', $pData)
                ->andWhere('de.status = :status')
                ->setParameter('status', DamagedEducator::STATUS_NEW)
                ->getQuery()
                ->getSingleScalarResult();

            $sumAmountNewTransactions = $transactionRepository->getSumAmountTransactions($pData, null, [Transaction::STATUS_NEW]);
            $sumAmountWaitingConfirmationTransactions = $transactionRepository->getSumAmountTransactions($pData, null, [Transaction::STATUS_WAITING_CONFIRMATION, Transaction::STATUS_EXPIRED]);
            $sumAmountConfirmedTransactions = $transactionRepository->getSumAmountTransactions($pData, null, [Transaction::STATUS_CONFIRMED]);
            $totalDamagedEducators = $entityManager->getRepository(DamagedEducator::class)->count(['period' => $pData]);
            $averageAmountPerDamagedEducator = 0;

            if ($sumAmountConfirmedTransactions > 0 && $totalDamagedEducators > 0) {
                $averageAmountPerDamagedEducator = floor($sumAmountConfirmedTransactions / $totalDamagedEducators);
            }

            $periodItems[] = [
                'entity' => $pData,
                'totalDamagedEducators' => $totalDamagedEducators,
                'sumAmountDamagedEducators' => $sumAmountDamagedEducators,
                'sumAmountNewTransactions' => $sumAmountNewTransactions,
                'sumAmountWaitingConfirmationTransactions' => $sumAmountWaitingConfirmationTransactions,
                'sumAmountConfirmedTransactions' => $sumAmountConfirmedTransactions,
                'averageAmountPerDamagedEducator' => $averageAmountPerDamagedEducator,
            ];
        }

        return $this->render('admin/home/index.html.twig', [
            'totalDonors' => $totalDonors,
            'totalMonthlyDonors' => $totalMonthlyDonors,
            'totalNonMonthlyDonors' => $totalNonMonthlyDonors,
            'sumAmountMonthlyDonors' => $sumAmountMonthlyDonors,
            'sumAmountNonMonthlyDonors' => $sumAmountNonMonthlyDonors,
            'totalDelegate' => $totalDelegates,
            'totalActiveSchools' => $totalActiveSchools,
            'totalAdmins' => $totalAdmins,
            'periodItems' => $periodItems,
        ]);
    }
}

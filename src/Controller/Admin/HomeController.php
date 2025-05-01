<?php

namespace App\Controller\Admin;

use App\Entity\DamagedEducator;
use App\Entity\DamagedEducatorPeriod;
use App\Entity\Transaction;
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
    public function index(EntityManagerInterface $entityManager, UserDonorRepository $userDonorRepository, UserRepository $userRepository, UserDelegateSchoolRepository $userDelegateSchoolRepository): Response
    {
        $totalDonors = $userDonorRepository->getTotal();
        $totalMonthlyDonors = $userDonorRepository->getTotalMonthly();
        $totalNonMonthlyDonors = $userDonorRepository->getTotalNonMonthly();
        $sumAmountMonthlyDonors = $userDonorRepository->sumAmountMonthlyDonors();
        $sumAmountNonMonthlyDonors = $userDonorRepository->sumAmountNonMonthlyDonors();
        $totalDelegates = $userRepository->getTotalDelegates();
        $totalActiveSchools = $userDelegateSchoolRepository->getTotalActiveSchools();
        $totalAdmins = $userRepository->getTotalAdmins();

        $period = $entityManager->getRepository(DamagedEducatorPeriod::class)->findAll();
        $periodItems = [];

        foreach ($period as $pData) {
            $qb = $entityManager->createQueryBuilder();
            $sumAmountDamagedEducators = $qb->select('SUM(de.amount)')
                ->from(DamagedEducator::class, 'de')
                ->andWhere('de.period = :period')
                ->setParameter('period', $pData)
                ->getQuery()
                ->getSingleScalarResult();

            $qb = $entityManager->createQueryBuilder();
            $sumAmountConfirmedTransactions = $qb->select('SUM(t.amount)')
                ->from(Transaction::class, 't')
                ->innerJoin('t.damagedEducator', 'de')
                ->andWhere('de.period = :period')
                ->setParameter('period', $pData)
                ->andWhere('t.status = :status')
                ->setParameter('status', Transaction::STATUS_CONFIRMED)
                ->getQuery()
                ->getSingleScalarResult();

            $qb = $entityManager->createQueryBuilder();
            $sumAmountWaitingConfirmationTransactions = $qb->select('SUM(t.amount)')
                ->from(Transaction::class, 't')
                ->innerJoin('t.damagedEducator', 'de')
                ->andWhere('de.period = :period')
                ->setParameter('period', $pData)
                ->andWhere('t.status = :status')
                ->setParameter('status', Transaction::STATUS_WAITING_CONFIRMATION)
                ->getQuery()
                ->getSingleScalarResult();

            $periodItems[] = [
                'entity' => $pData,
                'totalDamagedEducators' => $entityManager->getRepository(DamagedEducator::class)->count(['period' => $pData]),
                'sumAmountDamagedEducators' => $sumAmountDamagedEducators,
                'sumAmountConfirmedTransactions' => $sumAmountConfirmedTransactions,
                'sumAmountWaitingConfirmationTransactions' => $sumAmountWaitingConfirmationTransactions,
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

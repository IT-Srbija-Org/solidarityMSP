<?php

namespace App\Controller\Admin;

use App\Entity\DamagedEducator;
use App\Entity\DamagedEducatorPeriod;
use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\UserDelegateSchool;
use App\Entity\UserDonor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $userDonorRepository = $entityManager->getRepository(UserDonor::class);
        $userRepository = $entityManager->getRepository(User::class);
        $userDelegateSchoolRepository = $entityManager->getRepository(UserDelegateSchool::class);

        $totalDonors = $userDonorRepository->getTotal();
        $totalMonthlyDonors = $userDonorRepository->getTotalMonthly();
        $sumAmountMonthlyDonors = $userDonorRepository->sumAmountMonthlyDonors();
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

            $periodItems[] = [
                'entity' => $pData,
                'totalDamagedEducators' => $entityManager->getRepository(DamagedEducator::class)->count(['period' => $pData]),
                'sumAmountDamagedEducators' => $sumAmountDamagedEducators,
                'sumAmountConfirmedTransactions' => $sumAmountConfirmedTransactions,
            ];
        }

        return $this->render('admin/home/index.html.twig', [
            'totalDonors' => $totalDonors,
            'totalMonthlyDonors' => $totalMonthlyDonors,
            'sumAmountMonthlyDonors' => $sumAmountMonthlyDonors,
            'totalDelegate' => $totalDelegates,
            'totalActiveSchools' => $totalActiveSchools,
            'totalAdmins' => $totalAdmins,
            'periodItems' => $periodItems,
        ]);
    }
}

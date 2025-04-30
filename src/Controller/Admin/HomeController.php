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
        $qb = $entityManager->createQueryBuilder();
        $totalDonors = $qb->select('COUNT(ud.id)')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $entityManager->createQueryBuilder();
        $totalMonthlyDonors = $qb->select('COUNT(ud.id)')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('ud.isMonthly = 1')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $entityManager->createQueryBuilder();
        $sumAmountMonthlyDonors = $qb->select('SUM(ud.amount)')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('ud.isMonthly = 1')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $entityManager->createQueryBuilder();
        $totalDelegates = $qb->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DELEGATE%')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.isEmailVerified = 1')
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $entityManager->createQueryBuilder();
        $totalActiveSchools = $qb->select('COUNT(DISTINCT uds.school)')
            ->from(UserDelegateSchool::class, 'uds')
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $entityManager->createQueryBuilder();
        $totalAdmins = $qb->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->andWhere('u.isActive = 1')
            ->getQuery()
            ->getSingleScalarResult();

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
            'totalUsers' => $entityManager->getRepository(User::class)->count(['isActive' => 1, 'isEmailVerified' => 1]),
            'totalAdmins' => $totalAdmins,
            'periodItems' => $periodItems,
        ]);
    }
}

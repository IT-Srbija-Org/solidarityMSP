<?php

namespace App\Controller\Admin;

use App\Entity\City;
use App\Entity\Educator;
use App\Entity\School;
use App\Entity\User;
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
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $entityManager->createQueryBuilder();
        $totalDelegatesSum = $qb->select('SUM(ud.amount)')
            ->from(UserDonor::class, 'ud')
            ->innerJoin('ud.user', 'u')
            ->andWhere('u.isActive = 1')
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $entityManager->createQueryBuilder();
        $totalDelegates = $qb->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DELEGATE%')
            ->andWhere('u.isActive = 1')
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $entityManager->createQueryBuilder();
        $totalEducatorsSum = $qb->select('SUM(e.amount)')
            ->from(Educator::class, 'e')
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

        return $this->render('admin/home/index.html.twig', [
            'totalDonors' => $totalDonors,
            'totalDonorsSum' => $totalDelegatesSum,
            'totalDelegate' => $totalDelegates,
            'totalEducators' => $entityManager->getRepository(Educator::class)->count(),
            'totalEducatorsSum' => $totalEducatorsSum,
            'totalSchool' => $entityManager->getRepository(School::class)->count(),
            'totalCities' => $entityManager->getRepository(City::class)->count(),
            'totalUsers' => $entityManager->getRepository(User::class)->count(['isActive' => 1]),
            'totalAdmins' => $totalAdmins,
        ]);
    }
}

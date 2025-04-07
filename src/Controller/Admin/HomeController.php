<?php

namespace App\Controller\Admin;

use App\Entity\City;
use App\Entity\School;
use App\Entity\SchoolType;
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
        $totalDelegates = $qb->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DELEGATE%')
            ->getQuery()
            ->getSingleScalarResult();;

        $qb = $entityManager->createQueryBuilder();
        $totalDelegatesSum = $qb->select('SUM(ud.amount)')
            ->from(UserDonor::class, 'ud')
            ->getQuery()
            ->getSingleScalarResult();;

        return $this->render('admin/home/index.html.twig', [
            'totalDonors' => $entityManager->getRepository(UserDonor::class)->count(),
            'totalDonorsSum' => $totalDelegatesSum,
            'totalDelegate' => $totalDelegates,
            'totalSchool' => $entityManager->getRepository(School::class)->count(),
            'totalCities' => $entityManager->getRepository(City::class)->count(),
            'totalUsers' => $entityManager->getRepository(User::class)->count(),
        ]);
    }
}

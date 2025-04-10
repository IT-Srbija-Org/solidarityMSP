<?php

namespace App\Controller\Admin;

use App\Entity\City;
use App\Form\Admin\CityEditType;
use App\Form\Admin\CitySearchType;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/city', name: 'admin_city_')]
final class CityController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/list', name: 'list')]
    public function list(Request $request, CityRepository $cityRepository): Response
    {
        $form = $this->createForm(CitySearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);

        return $this->render('admin/city/list.html.twig', [
            'cities' => $cityRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $schoolType = new City();
        $form = $this->createForm(CityEditType::class, $schoolType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($schoolType);
            $this->entityManager->flush();

            $this->addFlash('success', 'Dodat je novi grad');

            return $this->redirectToRoute('admin_city_list');
        }

        return $this->render('admin/city/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, City $city): Response
    {
        $form = $this->createForm(CityEditType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($city);
            $this->entityManager->flush();

            $this->addFlash('success', 'Grad je izmenjen');

            return $this->redirectToRoute('admin_city_list');
        }

        return $this->render('admin/city/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

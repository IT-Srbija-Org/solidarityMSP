<?php

namespace App\Controller\Admin;

use App\Entity\School;
use App\Form\Admin\SchoolEditType;
use App\Form\Admin\SchoolSearchType;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/school', name: 'admin_school_')]
final class SchoolController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/list', name: 'list')]
    public function list(Request $request, SchoolRepository $schoolRepository): Response
    {
        $form = $this->createForm(SchoolSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);

        return $this->render('admin/school/list.html.twig', [
            'schools' => $schoolRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $school = new School();
        $form = $this->createForm(SchoolEditType::class, $school);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($school);
            $this->entityManager->flush();

            $this->addFlash('success', 'Dodata je nova škola');

            return $this->redirectToRoute('admin_school_list');
        }

        return $this->render('admin/school/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, School $school): Response
    {
        $form = $this->createForm(SchoolEditType::class, $school);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($school);
            $this->entityManager->flush();

            $this->addFlash('success', 'Škola je izmenjena');

            return $this->redirectToRoute('admin_school_list');
        }

        return $this->render('admin/school/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

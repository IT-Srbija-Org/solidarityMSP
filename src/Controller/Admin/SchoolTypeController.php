<?php

namespace App\Controller\Admin;

use App\Entity\SchoolType;
use App\Form\Admin\SchoolTypeEditType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/school-type', name: 'admin_school_type_')]
final class SchoolTypeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/list', name: 'list')]
    public function list(): Response
    {
        $items = $this->entityManager->getRepository(SchoolType::class)->findAll();

        return $this->render('admin/schoolType/list.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $schoolType = new SchoolType();
        $form = $this->createForm(SchoolTypeEditType::class, $schoolType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($schoolType);
            $this->entityManager->flush();

            $this->addFlash('success', 'Dodat je novi tip škole');

            return $this->redirectToRoute('admin_school_type_list');
        }

        return $this->render('admin/schoolType/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, SchoolType $schoolType): Response
    {
        $form = $this->createForm(SchoolTypeEditType::class, $schoolType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($schoolType);
            $this->entityManager->flush();

            $this->addFlash('success', 'Tip škole je izmenjen');

            return $this->redirectToRoute('admin_school_type_list');
        }

        return $this->render('admin/schoolType/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

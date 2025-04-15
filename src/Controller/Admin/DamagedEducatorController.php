<?php

namespace App\Controller\Admin;

use App\Form\Admin\DamagedEducatorSearchType;
use App\Repository\DamagedEducatorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/damaged-educator', name: 'admin_damaged_educator_')]
final class DamagedEducatorController extends AbstractController
{
    #[Route('/list', name: 'list')]
    public function list(Request $request, DamagedEducatorRepository $damagedEducatorRepository): Response
    {
        $form = $this->createForm(DamagedEducatorSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);

        return $this->render('admin/damagedEducator/list.html.twig', [
            'damagedEducators' => $damagedEducatorRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }
}

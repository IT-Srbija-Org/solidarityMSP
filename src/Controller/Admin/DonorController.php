<?php

namespace App\Controller\Admin;

use App\Form\Admin\DonorSearchType;
use App\Repository\UserDonorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/donor', name: 'admin_donor_')]
final class DonorController extends AbstractController
{
    #[Route('/list', name: 'list')]
    public function list(Request $request, UserDonorRepository $userDonorRepository): Response
    {
        $form = $this->createForm(DonorSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);

        return $this->render('admin/donor/list.html.twig', [
            'donors' => $userDonorRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }
}

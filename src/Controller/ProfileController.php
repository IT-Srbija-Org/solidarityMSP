<?php

namespace App\Controller;

use App\Form\ProfileEditType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil', name: 'profile_')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/izmena-podataka', name: 'edit')]
    public function edit(Request $request): Response
    {
        $form = $this->createForm(ProfileEditType::class, $this->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Podaci su uspešno izmenjeni');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/transakcije', name: 'transactions')]
    public function transactions(Request $request, TransactionRepository $transactionRepository): Response
    {
        $criteria = ['user' => $this->getUser()];
        $page = $request->query->getInt('page', 1);

        return $this->render('profile/transactions.html.twig', [
            'transactions' => $transactionRepository->search($criteria, $page),
        ]);
    }
}

<?php

namespace App\Controller;

use App\Form\ProfileEditType;
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

    #[Route(name: 'details')]
    public function details(): Response
    {
        $transactions = $this->getUser()->getTransactions();
        $totalTransactionsAmount = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->isUserDonorConfirmed() || $transaction->isStatusConfirmed()) {
                $totalTransactionsAmount += $transaction->getAmount();
            }
        }

        return $this->render('profile/details.html.twig', [
            'totalTransactions' => count($transactions),
            'totalTransactionsAmount' => $totalTransactionsAmount,
        ]);
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
}

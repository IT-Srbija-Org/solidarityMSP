<?php

namespace App\Controller\Admin;

use App\Entity\Transaction;
use App\Form\Admin\TransactionEditType;
use App\Form\Admin\TransactionSearchType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/transaction', name: 'admin_transaction_')]
final class TransactionController extends AbstractController
{
    #[Route('/list', name: 'list')]
    public function list(Request $request, TransactionRepository $transactionRepository): Response
    {
        $form = $this->createForm(TransactionSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);

        return $this->render('admin/transaction/list.html.twig', [
            'transactions' => $transactionRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TransactionEditType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($transaction);
            $entityManager->flush();

            $this->addFlash('success', 'Transakcija je uspešno izmenjena');

            return $this->redirectToRoute('admin_transaction_list');
        }

        return $this->render('admin/transaction/edit.html.twig', [
            'transaction' => $transaction,
            'form' => $form->createView(),
        ]);
    }
}

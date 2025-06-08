<?php

namespace App\Controller\Admin;

use App\Entity\Transaction;
use App\Entity\User;
use App\Form\Admin\TransactionEditType;
use App\Form\Admin\TransactionNewType;
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
    public function __construct(private EntityManagerInterface $entityManager, private TransactionRepository $transactionRepository)
    {
    }

    #[Route('/list', name: 'list')]
    public function list(Request $request): Response
    {
        $form = $this->createForm(TransactionSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);

        return $this->render('admin/transaction/list.html.twig', [
            'transactions' => $this->transactionRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Transaction $transaction): Response
    {
        $form = $this->createForm(TransactionEditType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $transaction->setStatusComment(null);
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();

            $this->addFlash('success', 'Transakcija je uspešno izmenjena');

            return $this->redirectToRoute('admin_transaction_list');
        }

        return $this->render('admin/transaction/edit.html.twig', [
            'transaction' => $transaction,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $transaction = new Transaction();
        $form = $this->createForm(TransactionNewType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userDonorEmail = $form->get('userDonorEmail')->getData();
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userDonorEmail]);

            $transaction->setUser($user);
            $transaction->setUserDonorFirstName($user->getFirstName());
            $transaction->setUserDonorLastName($user->getLastName());
            $transaction->setAccountNumber($transaction->getDamagedEducator()->getAccountNumber());

            $this->entityManager->persist($transaction);
            $this->entityManager->flush();

            $this->addFlash('success', 'Transakcija je uspešno dodata');

            return $this->redirectToRoute('admin_transaction_edit', [
                'id' => $transaction->getId(),
            ]);
        }

        return $this->render('admin/transaction/new.html.twig', [
            'transaction' => $transaction,
            'form' => $form->createView(),
        ]);
    }
}

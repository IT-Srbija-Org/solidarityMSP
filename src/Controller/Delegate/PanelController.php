<?php

namespace App\Controller\Delegate;

use App\Entity\DamagedEducator;
use App\Entity\Transaction;
use App\Entity\User;
use App\Form\ConfirmType;
use App\Form\DamagedEducatorEditType;
use App\Form\DamagedEducatorSearchType;
use App\Form\TransactionChangeStatusType;
use App\Repository\DamagedEducatorPeriodRepository;
use App\Repository\DamagedEducatorRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_DELEGATE')]
#[Route('/delegat', name: 'delegate_panel_')]
class PanelController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/odabir-perioda', name: 'damaged_educator_period')]
    public function damagedEducatorPeriod(DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository): Response
    {
        $items = $damagedEducatorPeriodRepository->findBy([], [
            'year' => 'DESC',
            'month' => 'DESC',
            'id' => 'DESC',
        ]);

        return $this->render('delegate/damaged_educator_period.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/osteceni', name: 'damaged_educators')]
    public function damagedEducators(
        Request $request,
        DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository,
        DamagedEducatorRepository $damagedEducatorRepository,
    ): Response {
        $periodId = $request->query->getInt('period');
        $period = $damagedEducatorPeriodRepository->find($periodId);
        if (empty($period)) {
            return $this->redirectToRoute('delegate_panel_damaged_educator_period');
        }

        $form = $this->createForm(DamagedEducatorSearchType::class, null, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);
        $criteria = [];

        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        /** @var User $user */
        $user = $this->getUser();

        $criteria['schools'] = [];
        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $criteria['schools'][] = $delegateSchool->getSchool()->getId();
        }

        $criteria['period'] = $period;
        $page = $request->query->getInt('page', 1);

        return $this->render('delegate/damaged_educators.html.twig', [
            'damagedEducators' => $damagedEducatorRepository->search($criteria, $page),
            'period' => $period,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/prijavi-ostecenog', name: 'new_damaged_educator')]
    public function newDamagedEducator(Request $request, DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository, DamagedEducatorRepository $damagedEducatorRepository): Response
    {
        $periodId = $request->query->getInt('period');
        $period = $damagedEducatorPeriodRepository->find($periodId);
        if (empty($period) || !$period->isActive()) {
            throw $this->createAccessDeniedException();
        }

        $damagedEducator = new DamagedEducator();
        $damagedEducator->setCreatedBy($this->getUser());
        $damagedEducator->setPeriod($period);

        $form = $this->createForm(DamagedEducatorEditType::class, $damagedEducator, [
            'user' => $this->getUser(),
            'entityManager' => $this->entityManager,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($damagedEducator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste sačuvali oštećenog.');

            return $this->redirectToRoute('delegate_panel_damaged_educators', [
                'period' => $damagedEducator->getPeriod()->getId(),
            ]);
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('delegate/edit_damaged_educator.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
            'damagedEducators' => $damagedEducatorRepository->getFromUser($user),
        ]);
    }

    #[Route('/osteceni/{id}/izmeni-podatke', name: 'edit_damaged_educator')]
    public function editDamagedEducator(Request $request, DamagedEducator $damagedEducator, DamagedEducatorRepository $damagedEducatorRepository): Response
    {
        if (!$damagedEducator->getPeriod()->isActive()) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $allowedSchools = [];
        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $allowedSchools[] = $delegateSchool->getSchool()->getId();
        }

        if (!in_array($damagedEducator->getSchool()->getId(), $allowedSchools)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(DamagedEducatorEditType::class, $damagedEducator, [
            'user' => $this->getUser(),
            'entityManager' => $this->entityManager,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $damagedEducator->setCreatedBy($this->getUser());
            $this->entityManager->persist($damagedEducator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste izmenili podatke od oštećenog.');

            return $this->redirectToRoute('delegate_panel_damaged_educators', [
                'period' => $damagedEducator->getPeriod()->getId(),
            ]);
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('delegate/edit_damaged_educator.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
            'damagedEducators' => $damagedEducatorRepository->getFromUser($user),
        ]);
    }

    #[Route('/osteceni/{id}/brisanje', name: 'delete_damaged_educator')]
    public function deleteDamagedEducator(Request $request, DamagedEducator $damagedEducator): Response
    {
        if (!$damagedEducator->getPeriod()->isActive()) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $allowedSchools = [];
        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $allowedSchools[] = $delegateSchool->getSchool()->getId();
        }

        if (!in_array($damagedEducator->getSchool()->getId(), $allowedSchools)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ConfirmType::class, null, [
            'message' => 'Potvrđujem da želim da obrišem oštećenog "'.$damagedEducator->getName().'".',
            'submit_message' => 'Potvrdi',
            'submit_class' => 'btn btn-error',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($damagedEducator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste obrisali oštećenog.');

            return $this->redirectToRoute('delegate_panel_damaged_educators', [
                'period' => $damagedEducator->getPeriod()->getId(),
            ]);
        }

        return $this->render('delegate/delete_damaged_educator.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
        ]);
    }

    #[Route('/osteceni/{id}/instrukcija-za-uplatu', name: 'damaged_educator_transactions')]
    public function damagedEducatorTransactions(DamagedEducator $damagedEducator, TransactionRepository $transactionRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $allowedSchools = [];
        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $allowedSchools[] = $delegateSchool->getSchool()->getId();
        }

        if (!in_array($damagedEducator->getSchool()->getId(), $allowedSchools)) {
            throw $this->createAccessDeniedException();
        }

        $hasCancelledTransactions = (bool) $transactionRepository->count([
            'damagedEducator' => $damagedEducator,
            'status' => Transaction::STATUS_CANCELLED,
        ]);

        return $this->render('delegate/damaged_educator_transactions.html.twig', [
            'damagedEducator' => $damagedEducator,
            'transactions' => $transactionRepository->findBy(['damagedEducator' => $damagedEducator]),
            'hasCancelledTransactions' => $hasCancelledTransactions,
        ]);
    }

    #[Route('/osteceni/instrukcija-za-uplatu/{id}/promena-statusa', name: 'damaged_educator_transaction_change_status')]
    public function damagedEducatorTransactionChangeStatus(Request $request, Transaction $transaction): Response
    {
        if (!$transaction->allowToChangeStatus()) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $allowedSchools = [];
        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $allowedSchools[] = $delegateSchool->getSchool()->getId();
        }

        $damagedEducator = $transaction->getDamagedEducator();
        if (!in_array($damagedEducator->getSchool()->getId(), $allowedSchools)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TransactionChangeStatusType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste promenili status instrukcije za uplatu.');

            return $this->redirectToRoute('delegate_panel_damaged_educator_transactions', [
                'id' => $damagedEducator->getId(),
            ]);
        }

        return $this->render('delegate/damaged_educator_transaction_change_status.html.twig', [
            'form' => $form,
            'transaction' => $transaction,
            'damagedEducator' => $damagedEducator,
        ]);
    }
}

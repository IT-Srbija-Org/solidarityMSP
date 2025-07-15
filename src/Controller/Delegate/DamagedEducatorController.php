<?php

namespace App\Controller\Delegate;

use App\Entity\City;
use App\Entity\DamagedEducator;
use App\Entity\Transaction;
use App\Entity\User;
use App\Form\DamagedEducatorDeleteType;
use App\Form\DamagedEducatorEditType;
use App\Form\DamagedEducatorImportType;
use App\Form\DamagedEducatorSearchType;
use App\Form\TransactionChangeStatusType;
use App\Repository\DamagedEducatorPeriodRepository;
use App\Repository\DamagedEducatorRepository;
use App\Repository\SchoolRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserDelegateSchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_DELEGATE')]
#[Route('/delegat', name: 'delegate_damaged_educator_')]
class DamagedEducatorController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager, private DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository, private DamagedEducatorRepository $damagedEducatorRepository, private TransactionRepository $transactionRepository)
    {
    }

    #[Route('/osteceni', name: 'list')]
    public function list(Request $request, DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository, DamagedEducatorRepository $damagedEducatorRepository, TransactionRepository $transactionRepository, UserDelegateSchoolRepository $userDelegateSchoolRepository, SchoolRepository $schoolRepository): Response
    {
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
        $isUniversity = false;
        $page = $request->query->getInt('page', 1);

        $totalDamagedEducators = $damagedEducatorRepository->getTotals(false);
        $sumAmountConfirmedTransactions = $transactionRepository->getSumAmountTransactions([Transaction::STATUS_CONFIRMED]);
        $averageAmountPerDamagedEducator = 0;
        if ($sumAmountConfirmedTransactions > 0 && $totalDamagedEducators > 0) {
            $averageAmountPerDamagedEducator = floor($sumAmountConfirmedTransactions / $totalDamagedEducators);
        }
        $statistics = [
            'totalDamagedEducators' => $totalDamagedEducators,
//            'totalActiveSchools' => $damagedEducatorRepository->getTotalsSchoolByPeriod($period),
            'sumAmountConfirmedTransactions' => $sumAmountConfirmedTransactions,
            'averageAmountPerDamagedEducator' => $averageAmountPerDamagedEducator,
            'schools' => [],
        ];

        $sumAmountDamagedEducators = $damagedEducatorRepository->getSumAmount(false);
        $sumAmountNewTransactions = $transactionRepository->getSumAmountTransactions([Transaction::STATUS_NEW]);
        $sumAmountWaitingConfirmationTransactions = $transactionRepository->getSumAmountTransactions([Transaction::STATUS_WAITING_CONFIRMATION, Transaction::STATUS_EXPIRED]);
        $sumAmountConfirmedTransactions = $transactionRepository->getSumAmountTransactions([Transaction::STATUS_CONFIRMED]);
        $totalDamagedEducators = $damagedEducatorRepository->getTotals(false);
        $averageAmountPerDamagedEducator = 0;
        if ($sumAmountConfirmedTransactions > 0 && $totalDamagedEducators > 0) {
            $averageAmountPerDamagedEducator = floor($sumAmountConfirmedTransactions / $totalDamagedEducators);
        }
        $periodItems = [
            'totalDamagedEducators' => $totalDamagedEducators,
            'sumAmountDamagedEducators' => $sumAmountDamagedEducators,
            'sumAmountNewTransactions' => $sumAmountNewTransactions,
            'sumAmountWaitingConfirmationTransactions' => $sumAmountWaitingConfirmationTransactions,
            'sumAmountConfirmedTransactions' => $sumAmountConfirmedTransactions,
            'averageAmountPerDamagedEducator' => $averageAmountPerDamagedEducator,
        ];

        return $this->render('delegate/damagedEducator/list.html.twig', [
            'statistics' => $statistics,
            'damagedEducators' => $damagedEducatorRepository->search($criteria, $page),
            'isUniversity' => $isUniversity,
            'details' => $periodItems,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/prijavi-ostecenog', name: 'new')]
    public function newDamagedEducator(Request $request, DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository, DamagedEducatorRepository $damagedEducatorRepository): Response
    {
        $damagedEducator = new DamagedEducator();
        $damagedEducator->setCreatedBy($this->getUser());

        $form = $this->createForm(DamagedEducatorEditType::class, $damagedEducator, [
            'user' => $this->getUser(),
            'entityManager' => $this->entityManager,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($damagedEducator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste sačuvali oštećenog.');

            return $this->redirectToRoute('delegate_damaged_educator_list');
        }

        return $this->render('delegate/damagedEducator/edit.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
            'damagedEducators' => $damagedEducatorRepository->findBy([]),
        ]);
    }

    #[Route('/osteceni/{id}/izmeni-podatke', name: 'edit')]
    public function editDamagedEducator(Request $request, DamagedEducator $damagedEducator, DamagedEducatorRepository $damagedEducatorRepository, TransactionRepository $transactionRepository): Response
    {
        if (!$damagedEducator->allowToEdit()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(DamagedEducatorEditType::class, $damagedEducator, [
            'user' => $this->getUser(),
            'entityManager' => $this->entityManager,
        ]);

        $currentAccountNumber = $damagedEducator->getAccountNumber();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $damagedEducator->setCreatedBy($this->getUser());
            $this->entityManager->persist($damagedEducator);
            $this->entityManager->flush();

            // If account number has changed, cancel all transactions
            if ($currentAccountNumber != $damagedEducator->getAccountNumber()) {
                $statuses = [
                    Transaction::STATUS_NEW,
                    Transaction::STATUS_WAITING_CONFIRMATION,
                ];

                $transactionRepository->cancelAllTransactions($damagedEducator, 'Instrukcija za uplatu je automatski otkazana pošto se promenio broj računa.', $statuses, false);
            }

            $this->addFlash('success', 'Uspešno ste izmenili podatke od oštećenog.');

            return $this->redirectToRoute('delegate_damaged_educator_list');
        }

        return $this->render('delegate/damagedEducator/edit.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
            'damagedEducators' => $damagedEducatorRepository->findBy([]),
        ]);
    }

    #[Route('/osteceni/{id}/brisanje', name: 'delete')]
    public function deleteDamagedEducator(Request $request, DamagedEducator $damagedEducator, TransactionRepository $transactionRepository): Response
    {
        if (!$damagedEducator->allowToDelete()) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(DamagedEducatorDeleteType::class, null, [
            'damagedEducator' => $damagedEducator,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $damagedEducator->setStatus(DamagedEducator::STATUS_DELETED);
            $damagedEducator->setStatusComment($data['comment']);
            $this->entityManager->flush();

            // Cancel transactions
            $transactionRepository->cancelAllTransactions($damagedEducator, 'Instrukcija za uplatu je otkazana pošto je oštećeni obrisan.', [Transaction::STATUS_NEW], true);

            $this->addFlash('success', 'Uspešno ste obrisali oštećenog.');

            return $this->redirectToRoute('delegate_damaged_educator_list');
        }

        return $this->render('delegate/damagedEducator/delete.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
        ]);
    }

    #[Route('/osteceni/{id}/vracanje-obrisanog', name: 'undelete')]
    public function undeleteDamagedEducator(DamagedEducator $damagedEducator): Response
    {
        if (!$damagedEducator->allowToUnDelete()) {
            throw $this->createAccessDeniedException();
        }

        $damagedEducator->setStatus(DamagedEducator::STATUS_NEW);
        $damagedEducator->setStatusComment(null);
        $this->entityManager->flush();

        $this->addFlash('success', 'Uspešno ste vratili obrisanog oštećenog.');

        return $this->redirectToRoute('delegate_damaged_educator_list');
    }

    #[Route('/osteceni/{id}/instrukcija-za-uplatu', name: 'transactions')]
    public function damagedEducatorTransactions(DamagedEducator $damagedEducator, TransactionRepository $transactionRepository): Response
    {
        if (!$damagedEducator->allowToViewTransactions()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('delegate/damagedEducator/transactions.html.twig', [
            'damagedEducator' => $damagedEducator,
            'transactions' => $transactionRepository->findBy(['damagedEducator' => $damagedEducator]),
        ]);
    }

    #[Route('/osteceni/instrukcija-za-uplatu/{id}/promena-statusa', name: 'transaction_change_status')]
    public function damagedEducatorTransactionChangeStatus(Request $request, Transaction $transaction): Response
    {
        if (!$transaction->allowToChangeStatus()) {
            throw $this->createAccessDeniedException();
        }

        $damagedEducator = $transaction->getDamagedEducator();

        $redirectPath = $request->query->get('redirectPath');
        if (empty($redirectPath)) {
            $redirectPath = $this->generateUrl('delegate_damaged_educator_transactions', [
                'id' => $damagedEducator->getId(),
            ]);
        }

        $form = $this->createForm(TransactionChangeStatusType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $transaction->setStatusComment(null);
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste promenili status instrukcije za uplatu.');

            return $this->redirect($redirectPath);
        }

        return $this->render('delegate/damagedEducator/transaction_change_status.html.twig', [
            'form' => $form,
            'transaction' => $transaction,
            'damagedEducator' => $damagedEducator,
            'redirectPath' => $redirectPath,
        ]);
    }

    #[Route('/instrukcije-za-proveru', name: 'pending_transactions')]
    public function pendingTransactions(Request $request): Response
    {
        $transactions = $this->transactionRepository->getPendingTransactions();

        return $this->render('delegate/damagedEducator/pending_transactions.html.twig', [
            'transactions' => $transactions,
        ]);
    }

    #[Route('/sacuvaj-instrukcije-za-proveru', name: 'pending_transactions_save')]
    public function pendingTransactionsSave(Request $request): JsonResponse
    {
        $items = json_decode($request->getContent(), true);
        if (empty($items)) {
            return $this->json([
                'success' => false,
            ]);
        }

        foreach ($items as $item) {
            if (empty($item['id']) || empty($item['value'])) {
                continue;
            }

            $transaction = $this->transactionRepository->find($item['id']);
            if (empty($transaction)) {
                continue;
            }

            if (!$transaction->allowToChangeStatus()) {
                continue;
            }

            if (!in_array($item['value'], [Transaction::STATUS_CONFIRMED, Transaction::STATUS_NOT_PAID])) {
                continue;
            }

            $transaction->setStatus($item['value']);
            $transaction->setStatusComment(null);
            $this->entityManager->persist($transaction);
        }

        $this->entityManager->flush();
        $this->addFlash('success', 'Uspešno ste sačuvali sve promene.');

        return $this->json([
            'success' => true,
        ]);
    }
}

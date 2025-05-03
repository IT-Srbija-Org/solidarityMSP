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
use App\Repository\TransactionRepository;
use App\Repository\UserDelegateSchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_DELEGATE')]
#[Route('/delegat', name: 'delegate_damaged_educator_')]
class DamagedEducatorController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/odabir-perioda', name: 'choose_period')]
    public function choosePeriod(DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository): Response
    {
        $items = $damagedEducatorPeriodRepository->findBy([], [
            'year' => 'DESC',
            'month' => 'DESC',
            'id' => 'DESC',
        ]);

        return $this->render('delegate/damagedEducator/choose_period.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/osteceni', name: 'list')]
    public function list(Request $request, DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository, DamagedEducatorRepository $damagedEducatorRepository, TransactionRepository $transactionRepository, UserDelegateSchoolRepository $userDelegateSchoolRepository): Response
    {
        $periodId = $request->query->getInt('period');
        $period = $damagedEducatorPeriodRepository->find($periodId);
        if (empty($period)) {
            return $this->redirectToRoute('delegate_damaged_educator_choose_period');
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
        $showImport = false;

        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $criteria['schools'][] = $delegateSchool->getSchool();

            if ($delegateSchool->getSchool()->showImport()) {
                $showImport = true;
            }
        }

        $criteria['period'] = $period;
        $page = $request->query->getInt('page', 1);

        $totalDamagedEducators = $damagedEducatorRepository->count(['period' => $period]);
        $sumAmountConfirmedTransactions = $transactionRepository->getSumAmountConfirmedTransactions($period, null);

        $averageAmountPerDamagedEducator = 0;
        if ($sumAmountConfirmedTransactions > 0 && $totalDamagedEducators > 0) {
            $averageAmountPerDamagedEducator = floor($sumAmountConfirmedTransactions / $totalDamagedEducators);
        }

        $statistics = [
            'totalDamagedEducators' => $totalDamagedEducators,
            'totalActiveSchools' => $userDelegateSchoolRepository->getTotalActiveSchools($period),
            'averageAmountPerDamagedEducator' => $averageAmountPerDamagedEducator,
            'schools' => [],
        ];

        foreach ($criteria['schools'] as $school) {
            $sumAmountConfirmedTransactions = $transactionRepository->getSumAmountConfirmedTransactions($period, $school);
            $totalDamagedEducators = $damagedEducatorRepository->count(['period' => $period, 'school' => $school]);

            $averageAmountPerDamagedEducator = 0;
            if ($sumAmountConfirmedTransactions > 0 && $totalDamagedEducators > 0) {
                $averageAmountPerDamagedEducator = floor($sumAmountConfirmedTransactions / $totalDamagedEducators);
            }

            $statistics['schools'][] = [
                'entity' => $school,
                'totalDamagedEducators' => $damagedEducatorRepository->count(['period' => $period, 'school' => $school]),
                'sumAmount' => $damagedEducatorRepository->getSumAmount($period, $school),
                'sumAmountConfirmedTransactions' => $sumAmountConfirmedTransactions,
                'averageAmountPerDamagedEducator' => $averageAmountPerDamagedEducator,
            ];
        }

        return $this->render('delegate/damagedEducator/list.html.twig', [
            'statistics' => $statistics,
            'damagedEducators' => $damagedEducatorRepository->search($criteria, $page),
            'showImport' => $showImport,
            'period' => $period,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/prijavi-ostecenog', name: 'new')]
    public function newDamagedEducator(Request $request, DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository, DamagedEducatorRepository $damagedEducatorRepository): Response
    {
        $periodId = $request->query->getInt('period');
        $period = $damagedEducatorPeriodRepository->find($periodId);
        if (empty($period)) {
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

            return $this->redirectToRoute('delegate_damaged_educator_list', [
                'period' => $damagedEducator->getPeriod()->getId(),
            ]);
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('delegate/damagedEducator/edit.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
            'damagedEducators' => $damagedEducatorRepository->getFromUser($user),
        ]);
    }

    #[Route('/prijavi-ostecene', name: 'import')]
    public function importDamagedEducators(Request $request, DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository, ValidatorInterface $validator): Response
    {
        $periodId = $request->query->getInt('period');
        $period = $damagedEducatorPeriodRepository->find($periodId);
        if (empty($period)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(DamagedEducatorImportType::class, null, [
            'user' => $this->getUser(),
            'entityManager' => $this->entityManager,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $school = $form->get('school')->getData();

            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();

            // Get the total rows
            $totalRows = $worksheet->getHighestRow();

            $errors = [];
            $this->entityManager->beginTransaction();

            for ($row = 2; $row <= $totalRows; ++$row) {
                $rowData = $worksheet->rangeToArray('A'.$row.':'.$worksheet->getHighestColumn().$row, null, true, false)[0];

                $damagedEducator = new DamagedEducator();
                $damagedEducator->setName($rowData[0] ?? '');
                $damagedEducator->setAccountNumber($rowData[2] ?? '');
                $damagedEducator->setAmount(empty($rowData[3]) ? 0 : (int) $rowData[3]);
                $damagedEducator->setSchool($school);

                $cityName = $rowData[1] ?? '';
                $city = $this->entityManager->getRepository(City::class)->findOneBy(['name' => $cityName]);
                $damagedEducator->setCity($city);

                $damagedEducator->setCreatedBy($this->getUser());
                $damagedEducator->setPeriod($period);

                $validations = $validator->validate($damagedEducator);
                foreach ($validations as $validation) {
                    $errors[$row][] = $validation->getMessage();
                }

                if ($cityName && empty($city)) {
                    $errors[$row][] = 'Grad nije pronađen u bazi';
                }

                if (0 == count($validations)) {
                    $this->entityManager->persist($damagedEducator);
                    $this->entityManager->flush();
                }
            }

            if (!empty($errors)) {
                $this->entityManager->rollBack();

                return $this->render('delegate/damagedEducator/import.html.twig', [
                    'form' => $form->createView(),
                    'period' => $period,
                    'errors' => $errors,
                ]);
            }

            $this->entityManager->commit();
            $this->addFlash('success', 'Uspešno ste sačuvali sve oštećene iz fajla (Ukupno: '.$totalRows.').');

            return $this->redirectToRoute('delegate_damaged_educator_list', [
                'period' => $period->getId(),
            ]);
        }

        return $this->render('delegate/damagedEducator/import.html.twig', [
            'form' => $form->createView(),
            'period' => $period,
        ]);
    }

    #[Route('/osteceni/{id}/izmeni-podatke', name: 'edit')]
    public function editDamagedEducator(Request $request, DamagedEducator $damagedEducator, DamagedEducatorRepository $damagedEducatorRepository, TransactionRepository $transactionRepository): Response
    {
        if (!$damagedEducator->allowToEdit()) {
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

        $currentAccountNumber = $damagedEducator->getAccountNumber();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $damagedEducator->setCreatedBy($this->getUser());
            $this->entityManager->persist($damagedEducator);
            $this->entityManager->flush();

            // If account number has changed, cancel all "NEW" transactions
            if ($currentAccountNumber != $damagedEducator->getAccountNumber()) {
                $transactionRepository->cancelAllNewTransactions($damagedEducator, 'Instruckija za uplatu je automatski otkazana pošto se promenio broj računa.');
            }

            $this->addFlash('success', 'Uspešno ste izmenili podatke od oštećenog.');

            return $this->redirectToRoute('delegate_damaged_educator_list', [
                'period' => $damagedEducator->getPeriod()->getId(),
            ]);
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('delegate/damagedEducator/edit.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
            'damagedEducators' => $damagedEducatorRepository->getFromUser($user),
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

        $allowedSchools = [];
        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $allowedSchools[] = $delegateSchool->getSchool()->getId();
        }

        if (!in_array($damagedEducator->getSchool()->getId(), $allowedSchools)) {
            throw $this->createAccessDeniedException();
        }

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
            $transactionRepository->cancelAllNewTransactions($damagedEducator, 'Instruckija za uplatu je otkazana pošto je oštećeni obrisan.');

            $this->addFlash('success', 'Uspešno ste obrisali oštećenog.');

            return $this->redirectToRoute('delegate_damaged_educator_list', [
                'period' => $damagedEducator->getPeriod()->getId(),
            ]);
        }

        return $this->render('delegate/damagedEducator/delete.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
        ]);
    }

    #[Route('/osteceni/{id}/instrukcija-za-uplatu', name: 'transactions')]
    public function damagedEducatorTransactions(DamagedEducator $damagedEducator, TransactionRepository $transactionRepository): Response
    {
        if (!$damagedEducator->allowToViewTransactions()) {
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

        $hasCancelledTransactions = (bool) $transactionRepository->count([
            'damagedEducator' => $damagedEducator,
            'status' => Transaction::STATUS_CANCELLED,
        ]);

        $hasExpiredTransactions = (bool) $transactionRepository->count([
            'damagedEducator' => $damagedEducator,
            'status' => Transaction::STATUS_EXPIRED,
        ]);

        return $this->render('delegate/damagedEducator/transactions.html.twig', [
            'damagedEducator' => $damagedEducator,
            'transactions' => $transactionRepository->findBy(['damagedEducator' => $damagedEducator]),
            'hasCancelledTransactions' => $hasCancelledTransactions,
            'hasExpiredTransactions' => $hasExpiredTransactions,
        ]);
    }

    #[Route('/osteceni/instrukcija-za-uplatu/{id}/promena-statusa', name: 'transaction_change_status')]
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

            return $this->redirectToRoute('delegate_damaged_educator_transactions', [
                'id' => $damagedEducator->getId(),
            ]);
        }

        return $this->render('delegate/damagedEducator/transaction_change_status.html.twig', [
            'form' => $form,
            'transaction' => $transaction,
            'damagedEducator' => $damagedEducator,
        ]);
    }
}

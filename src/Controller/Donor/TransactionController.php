<?php

namespace App\Controller\Donor;

use App\Entity\Transaction;
use App\Entity\User;
use App\Form\ProfileTransactionConfirmPaymentType;
use App\Form\TransactionCreateType;
use App\Repository\TransactionRepository;
use App\Service\CreateTransactionService;
use App\Service\InvoiceSlipService;
use App\Service\IpsQrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(name: 'donor_transaction_')]
#[IsGranted('ROLE_USER')]
class TransactionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceSlipService $invoiceSlipService,
        private IpsQrCodeService $qrCodeService,
        private CreateTransactionService $createTransactionService,
        private TransactionRepository $transactionRepository,
    ) {
    }

    #[Route('/instrukcije-za-uplatu', name: 'list')]
    public function list(Request $request, TransactionRepository $transactionRepository): Response
    {
        $criteria = ['user' => $this->getUser()];
        $page = $request->query->getInt('page', 1);

        $hasCancelledTransactions = (bool) $transactionRepository->count([
            'user' => $this->getUser(),
            'status' => Transaction::STATUS_CANCELLED,
        ]);

        $hasNotPaidTransactions = (bool) $transactionRepository->count([
            'user' => $this->getUser(),
            'status' => Transaction::STATUS_NOT_PAID,
            'userDonorConfirmed' => false,
        ]);

        $hasExpiredTransactions = (bool) $transactionRepository->count([
            'user' => $this->getUser(),
            'status' => Transaction::STATUS_EXPIRED,
        ]);

        return $this->render('donor/transaction/list.html.twig', [
            'transactions' => $transactionRepository->search($criteria, $page),
            'hasCancelledTransactions' => $hasCancelledTransactions,
            'hasNotPaidTransactions' => $hasNotPaidTransactions,
            'hasExpiredTransactions' => $hasExpiredTransactions,
        ]);
    }

    #[Route('/instrukcije-za-uplatu/ostampaj/{id}', name: 'print', requirements: ['id' => '\d+'])]
    public function print(Transaction $transaction): Response
    {
        /* @var User $user */
        $user = $this->getUser();
        if ($transaction->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$transaction->allowShowPrint()) {
            throw $this->createAccessDeniedException();
        }

        // Prepare slip data and background info using the service
        $data = $this->invoiceSlipService->prepareSlipData($transaction, $user);
        $bgInfo = $this->invoiceSlipService->getSlipBackgroundInfo();

        // Render Twig template
        $html = $this->renderView('donor/transaction/print.html.twig', array_merge($data, $bgInfo));

        // Generate PDF with Dompdf using the service
        $pdfContent = $this->invoiceSlipService->generatePdfFromHtml($html, $bgInfo['img_width'], $bgInfo['img_height']);

        $filename = 'instrukcija_za_uplatu_'.$transaction->getId().'.pdf';

        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    #[Route('/instrukcije-za-uplatu/qr/{id}', name: 'qr', requirements: ['id' => '\d+'])]
    public function qr(Transaction $transaction): Response
    {
        /* @var User $user */
        $user = $this->getUser();
        if ($transaction->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$transaction->allowShowQR()) {
            throw $this->createAccessDeniedException();
        }

        $paymentData = [
            'bankAccountNumber' => $transaction->getAccountNumber(),
            'payeeName' => $transaction->getDamagedEducator()->getName(),
            'payeeCityName' => $transaction->getDamagedEducator()->getCity()->getName(),
            'amount' => number_format($transaction->getAmount(), 2, ',', ''),
            'paymentCode' => '289',
            'paymentPurpose' => 'Transakcija po nalogu građana',
            'referenceCode' => $transaction->getReferenceCode(),
        ];

        $qrString = $this->qrCodeService->createIpsQrString($paymentData);
        $qrDataUri = $this->qrCodeService->getQrCodeDataUri($qrString);

        return $this->render('donor/transaction/qr_modal_content.html.twig', [
            'qrDataUri' => $qrDataUri,
            'transaction' => $transaction,
        ]);
    }

    #[Route('/instrukcije-za-uplatu/potvrdi-uplatu/{id}', name: 'confirm_payment', requirements: ['id' => '\d+'])]
    public function confirmPayment(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        /* @var User $user */
        $user = $this->getUser();
        if ($transaction->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$transaction->allowConfirmPayment()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProfileTransactionConfirmPaymentType::class, null, [
            'user' => $user,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $firstName = $form->get('firstName')->getData();
            $lastName = $form->get('lastName')->getData();

            $transaction->setUserDonorFirstName($firstName);
            $transaction->setUserDonorLastName($lastName);
            $transaction->setStatus(Transaction::STATUS_WAITING_CONFIRMATION);
            $transaction->setUserDonorConfirmed(true);
            $transaction->setStatusComment(null);
            $entityManager->persist($transaction);
            $entityManager->flush();

            if (empty($user->getFirstName()) && empty($user->getLastName())) {
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            $educatorName = $transaction->getDamagedEducator()->getName();
            $educatorFirstName = explode(' ', $educatorName)[0];

            $this->addFlash('success', 'Uspešno si potvrdio/la uplatu. '.$educatorFirstName.' ti se zahvaljuje na podršci ❤');

            return $this->redirectToRoute('donor_transaction_list');
        }

        return $this->render('donor/transaction/confirm_payment.html.twig', [
            'form' => $form->createView(),
            'transaction' => $transaction,
        ]);
    }

    #[Route('/doniraj', name: 'create')]
    public function create(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        $userDonor = $user->getUserDonor();

        $haveWaitingTransactions = $this->transactionRepository->count([
            'user' => $user,
            'status' => Transaction::STATUS_NEW,
        ]);

        $form = $this->createForm(TransactionCreateType::class, null, [
            'haveWaitingTransactions' => $haveWaitingTransactions,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $amount = $form->get('amount')->getData();
            $this->createTransactionService->create($userDonor, $amount);

            $this->addFlash('success', 'Kreirane su ti instrukcije za uplatu, ostalo je samo da ih uplatiš i potvrdiš uplatu.');

            return $this->redirectToRoute('donor_transaction_list');
        }

        return $this->render('donor/transaction/create.html.twig', [
            'form' => $form->createView(),
            'haveWaitingTransactions' => $haveWaitingTransactions,
        ]);
    }
}

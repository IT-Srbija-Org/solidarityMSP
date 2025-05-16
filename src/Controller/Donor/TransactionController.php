<?php

namespace App\Controller\Donor;

use App\Entity\Transaction;
use App\Entity\User;
use App\Form\ConfirmType;
use App\Form\ProfileTransactionConfirmPaymentType;
use App\Repository\TransactionRepository;
use App\Service\InvoiceSlipService;
use App\Service\IpsQrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/instrukcije-za-uplatu', name: 'donor_transaction_')]
#[IsGranted('ROLE_USER')]
class TransactionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceSlipService $invoiceSlipService,
        private IpsQrCodeService $qrCodeService,
    ) {
    }

    #[Route(name: 'list')]
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

    #[Route('/ostampaj/{id}', name: 'print', requirements: ['id' => '\d+'])]
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

    #[Route('/qr/{id}', name: 'qr', requirements: ['id' => '\d+'])]
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

    #[Route('/potvrdi-uplatu/{id}', name: 'confirm_payment', requirements: ['id' => '\d+'])]
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

        $form = $this->createForm(ProfileTransactionConfirmPaymentType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $transaction->setStatus(Transaction::STATUS_WAITING_CONFIRMATION);
            $transaction->setUserDonorConfirmed(true);
            $transaction->setStatusComment(null);
            $entityManager->persist($transaction);
            $entityManager->flush();

            $this->addFlash('success', 'Uspešno ste potvrdili uplatu.');

            return $this->redirectToRoute('donor_transaction_list');
        }

        return $this->render('donor/transaction/confirm_payment.html.twig', [
            'form' => $form->createView(),
            'transaction' => $transaction,
        ]);
    }

    #[Route('/obrisi-potvrdu-o-uplati/{id}', name: 'delete_payment_confirmation', requirements: ['id' => '\d+'])]
    public function deletePaymentConfirmation(Request $request, Transaction $transaction): Response
    {
        /* @var User $user */
        $user = $this->getUser();
        if ($transaction->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$transaction->allowDeletePaymentConfirmation()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ConfirmType::class, null, [
            'message' => 'Potvrđujem da želim da obrišem potvrdu o uplati',
            'submit_message' => 'Potvrdi',
            'submit_class' => 'btn btn-error',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $transaction->setStatus(Transaction::STATUS_NEW);
            $transaction->setUserDonorConfirmed(false);
            $transaction->setStatusComment(null);
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste obrisali potvrdu o uplati.');

            return $this->redirectToRoute('donor_transaction_list');
        }

        return $this->render('confirm_message.html.twig', [
            'iconClass' => 'square-x',
            'title' => 'Brisanje potvrde o uplati',
            'form' => $form->createView(),
        ]);
    }
}

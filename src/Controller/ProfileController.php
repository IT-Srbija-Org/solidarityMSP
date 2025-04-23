<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Form\ConfirmType;
use App\Form\ProfileEditType;
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

#[Route('/profil', name: 'profile_')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceSlipService $invoiceSlipService,
        private IpsQrCodeService $qrCodeService,
    ) {
    }

    #[Route('/stampaj-fakturu/{id}', name: 'transaction_invoice_print', requirements: ['id' => '\d+'])]
    public function printInvoice(Transaction $transaction): Response
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
        $html = $this->renderView('profile/invoice_slip.html.twig', array_merge($data, $bgInfo));

        // Generate PDF with Dompdf using the service
        $pdfContent = $this->invoiceSlipService->generatePdfFromHtml($html, $bgInfo['img_width'], $bgInfo['img_height']);

        $filename = 'faktura_'.$transaction->getId().'.pdf';

        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    #[Route('/qr-kod/{id}', name: 'transaction_qr', requirements: ['id' => '\d+'])]
    public function transactionQr(Transaction $transaction): Response
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
            'amount' => number_format($transaction->getAmount(), 2, ',', ''),
            'payerName' => $user->getFullName(),
            'paymentCode' => '289',
            'paymentPurpose' => 'Transakcija po nalogu građana',
        ];

        $qrString = $this->qrCodeService->createIpsQrString($paymentData);
        $qrDataUri = $this->qrCodeService->getQrCodeDataUri($qrString);

        return $this->render('profile/qr_modal_content.html.twig', [
            'qrDataUri' => $qrDataUri,
            'transaction' => $transaction,
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

    #[Route('/instrukcije-za-uplatu', name: 'transactions')]
    public function transactions(Request $request, TransactionRepository $transactionRepository): Response
    {
        $criteria = ['user' => $this->getUser()];
        $page = $request->query->getInt('page', 1);

        $hasCancelledTransactions = (bool) $transactionRepository->count([
            'user' => $this->getUser(),
            'status' => Transaction::STATUS_CANCELLED,
        ]);

        return $this->render('profile/transactions.html.twig', [
            'transactions' => $transactionRepository->search($criteria, $page),
            'hasCancelledTransactions' => $hasCancelledTransactions,
        ]);
    }

    #[Route('/potvrdi-uplatu/{id}', name: 'transaction_confirm_payment', requirements: ['id' => '\d+'])]
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

            $uploadedFile = $form->get('file')->getData();
            $uploadDir = $this->getParameter('PAYMENT_PROOF_DIR');

            if ($uploadedFile) {
                $filename = md5(uniqid(true).microtime()).'.'.$uploadedFile->guessExtension();
                $uploadedFile->move($uploadDir, $filename);
                $transaction->setPaymentProofFile($filename);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Uspešno ste potvrdili uplatu.');

            return $this->redirectToRoute('profile_transactions');
        }

        return $this->render('profile/transaction_confirm_payment.html.twig', [
            'form' => $form->createView(),
            'transaction' => $transaction,
        ]);
    }

    #[Route('/obrisi-potvrdu-o-uplati/{id}', name: 'transaction_delete_payment_confirmation', requirements: ['id' => '\d+'])]
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
            if ($transaction->hasPaymentProofFile()) {
                $uploadDir = $this->getParameter('PAYMENT_PROOF_DIR');
                $filePath = $uploadDir.'/'.$transaction->getPaymentProofFile();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $transaction->setStatus(Transaction::STATUS_NEW);
            $transaction->setPaymentProofFile(null);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste obrisali potvrdu o uplati.');

            return $this->redirectToRoute('profile_transactions');
        }

        return $this->render('confirm_message.html.twig', [
            'iconClass' => 'file-x',
            'title' => 'Brisanje potvrde o uplati',
            'form' => $form->createView(),
        ]);
    }
}

<?php
namespace App\Controller;

use App\Entity\Transaction;
use App\Form\ConfirmType;
use App\Form\ProfileEditType;
use App\Form\ProfileTransactionPaymentProofType;
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

    #[Route('/stampaj-fakturu/{id}', name: 'transaction_invoice_print', requirements: ['id' => '\d+'])]
    public function printInvoice(Transaction $transaction): Response
    {
        /* @var User $user */
        $user = $this->getUser();
        if ($transaction->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // Prepare data for the payment slip
        $payer     = $user->getFullName();
        $recipient = $transaction->getDamagedEducator()->getName();

        $data = [
            'payer'        => $payer,
            'recipient'    => $recipient,
            'purpose'      => 'Transakcija po nalogu građana',
            'amount'       => number_format($transaction->getAmount(), 2, ',', ''),
            'account'      => $transaction->getAccountNumber(),
            'reference'    => '',
            'place'        => '',
            'date'         => $transaction->getCreatedAt() ? $transaction->getCreatedAt()->format('d.m.Y') : '',
            'model'        => '',
            'currency'     => 'RSD',
            'payment_code' => '289',
        ];

        // Load background image and get dimensions
        $imagePath = $this->getParameter('kernel.project_dir') . '/public/image/nalog-za-uplatu.png';
        if (! file_exists($imagePath)) {
            throw $this->createNotFoundException('Background image not found.');
        }
        [$imgWidth, $imgHeight] = getimagesize($imagePath);
        $imageData              = base64_encode(file_get_contents($imagePath));
        $bgUrl                  = 'data:image/png;base64,' . $imageData;

        // Render Twig template
        $html = $this->renderView('profile/invoice_slip.html.twig', array_merge($data, [
            'bg_url'     => $bgUrl,
            'img_width'  => $imgWidth,
            'img_height' => $imgHeight,
        ]));

        // Generate PDF with Dompdf
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml($html);
        // Set paper size to match image pixel dimensions at 96 DPI (1pt = 1/72in)
        $pageWidthPt  = $imgWidth * 72 / 96;
        $pageHeightPt = $imgHeight * 72 / 96;
        $dompdf->setPaper([0, 0, $pageWidthPt, $pageHeightPt], 'portrait');
        $dompdf->render();

        $filename = 'faktura_' . $transaction->getId() . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
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
        $page     = $request->query->getInt('page', 1);

        return $this->render('profile/transactions.html.twig', [
            'transactions' => $transactionRepository->search($criteria, $page),
        ]);
    }

    #[Route('/prilozi-potvrdu-o-uplati/{id}', name: 'transaction_payment_proof_upload', requirements: ['id' => '\d+'])]
    public function uploadPaymentProof(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        /* @var User $user */
        $user = $this->getUser();

        if ($transaction->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProfileTransactionPaymentProofType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('paymentProofFile')->getData();
            $uploadDir    = $this->getParameter('PAYMENT_PROOF_DIR');

            // Remove old file
            if ($transaction->hasPaymentProofFile()) {
                $filename = $transaction->getPaymentProofFile();
                $filePath = $uploadDir . '/' . $filename;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            if ($uploadedFile) {
                $filename = md5(uniqid(true) . microtime()) . '.' . $uploadedFile->guessExtension();
                $uploadedFile->move($uploadDir, $filename);

                $transaction->setPaymentProofFile($filename);
                $entityManager->flush();

                $this->addFlash('success', 'Potvrda je uspešno uploadovan.');

                return $this->redirectToRoute('profile_transactions');
            }
        }

        return $this->render('profile/transaction_file.html.twig', [
            'form'        => $form->createView(),
            'transaction' => $transaction,
        ]);
    }

    #[Route('/preuzmi-potvrdu-o-uplati/{id}', name: 'transaction_payment_proof_download', requirements: ['id' => '\d+'])]
    public function downloadPaymentProof(Transaction $transaction): Response
    {
        /* @var User $user */
        $user = $this->getUser();

        if ($transaction->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $uploadDir = $this->getParameter('PAYMENT_PROOF_DIR');
        $filePath  = $uploadDir . '/' . $transaction->getPaymentProofFile();
        if (! file_exists($filePath)) {
            throw $this->createNotFoundException();
        }

        return $this->file($filePath);
    }

    #[Route('/obrisi-potvrdu-o-uplati/{id}', name: 'transaction_payment_proof_delete', requirements: ['id' => '\d+'])]
    public function deletePaymentProof(Request $request, Transaction $transaction): Response
    {
        /* @var User $user */
        $user = $this->getUser();

        if ($transaction->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (! $transaction->hasPaymentProofFile()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ConfirmType::class, null, [
            'message'        => 'Potvrđujem da želim da obrišem potvrdu o uplati',
            'submit_message' => 'Potvrdi',
            'submit_class'   => 'btn btn-error',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('PAYMENT_PROOF_DIR');
            $filePath  = $uploadDir . '/' . $transaction->getPaymentProofFile();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $transaction->setPaymentProofFile(null);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste obrisali potvrdu o uplati.');

            return $this->redirectToRoute('profile_transactions');
        }

        return $this->render('confirm_message.html.twig', [
            'iconClass' => 'file-x',
            'title'     => 'Brisanje potvrde o uplati',
            'form'      => $form->createView(),
        ]);
    }
}

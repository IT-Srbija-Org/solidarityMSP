<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class InvoiceSlipService
{
    public function __construct(private ParameterBagInterface $params)
    {
    }

    /**
     * Prepare slip data array for rendering.
     */
    public function prepareSlipData(Transaction $transaction, User $user): array
    {
        $cityName = $transaction->getDamagedEducator()->getCity()->getName();

        return [
            'payer' => $user->getFullName(),
            'recipient' => $transaction->getDamagedEducator()->getName().', '.$cityName,
            'purpose' => 'Transakcija po nalogu građana',
            'amount' => number_format($transaction->getAmount(), 2, ',', ''),
            'account' => $transaction->getAccountNumber(),
            'reference' => '',
            'place' => '',
            'date' => $transaction->getCreatedAt() ? $transaction->getCreatedAt()->format('d.m.Y') : '',
            'model' => '',
            'currency' => 'RSD',
            'payment_code' => '289',
        ];
    }

    /**
     * Load and encode the background image, returning its data and dimensions.
     */
    public function getSlipBackgroundInfo(): array
    {
        $imagePath = $this->params->get('kernel.project_dir').'/public/image/nalog-za-uplatu.png';
        if (!file_exists($imagePath)) {
            throw new \RuntimeException('Background image not found.');
        }
        [$imgWidth, $imgHeight] = getimagesize($imagePath);
        $imageData = base64_encode(file_get_contents($imagePath));
        $bgUrl = 'data:image/png;base64,'.$imageData;

        return [
            'bg_url' => $bgUrl,
            'img_width' => $imgWidth,
            'img_height' => $imgHeight,
        ];
    }

    /**
     * Generate a PDF from HTML and image dimensions.
     */
    public function generatePdfFromHtml(string $html, int $imgWidth, int $imgHeight): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Courier');
        $dompdf = new Dompdf($options);

        // Convert to valid UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        $dompdf->loadHtml($html);

        // Set paper size to match image pixel dimensions at 96 DPI (1pt = 1/72in)
        $pageWidthPt = $imgWidth * 72 / 96;
        $pageHeightPt = $imgHeight * 72 / 96;
        $dompdf->setPaper([0, 0, $pageWidthPt, $pageHeightPt], 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}

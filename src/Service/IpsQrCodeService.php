<?php

namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class IpsQrCodeService
{
    /**
     * Generate the IPS QR string for Serbian payments.
     *
     * @throws \InvalidArgumentException if required fields are missing
     */
    public function createIpsQrString(array $data): string
    {
        $required = [
            'bankAccountNumber',
            'payeeName',
            'payeeCityName',
            'amount',
            'payerName',
            'paymentPurpose',
        ];

        foreach ($required as $key) {
            if (empty($data[$key])) {
                throw new \InvalidArgumentException("Missing required field: $key");
            }
        }

        $identificationCode = !empty($data['identificationCode']) ? $data['identificationCode'] : 'PR';
        $version = !empty($data['version']) ? $data['version'] : '01';
        $characterSet = !empty($data['characterSet']) ? $data['characterSet'] : '1';

        $segments = [
            'K:'.$identificationCode,
            'V:'.$version,
            'C:'.$characterSet,
            'R:'.$data['bankAccountNumber'],
            'N:'.$data['payeeName']."\r\nNOTPROVIDED\r\n".$data['payeeCityName'],
            'I:RSD'.$data['amount'],
        ];

        if (!empty($data['payerName'])) {
            $segments[] = 'P:'.$data['payerName'];
        }
        if (!empty($data['paymentCode'])) {
            $segments[] = 'SF:'.$data['paymentCode'];
        }
        if (!empty($data['paymentPurpose'])) {
            $segments[] = 'S:'.$data['paymentPurpose'];
        }
        if (!empty($data['referenceCode'])) {
            $segments[] = 'RO:'.$data['referenceCode'];
        }

        return implode('|', $segments);
    }

    /**
     * Generate a QR code PNG as a data URI for embedding in HTML.
     */
    public function getQrCodeDataUri(string $qrString): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $qrString,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 320,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $result = $builder->build();

        return $result->getDataUri();
    }
}

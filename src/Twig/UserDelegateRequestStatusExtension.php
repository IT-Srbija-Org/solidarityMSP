<?php

namespace App\Twig;

use App\Entity\UserDelegateRequest;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UserDelegateRequestStatusExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('userDelegateRequestStatus', [$this, 'getStatus']),
        ];
    }

    public function getStatus(int $status): string
    {
        $allStatus = UserDelegateRequest::STATUS;
        if (empty($allStatus[$status])) {
            return 'None';
        }

        $iconClass = match ($status) {
            UserDelegateRequest::STATUS_NEW => 'ti-help text-warning',
            UserDelegateRequest::STATUS_CONFIRMED => 'ti-circle-check text-success',
            UserDelegateRequest::STATUS_REJECTED => 'ti-xbox-x text-error',
        };

        return "<span class='ti ".$iconClass." text-xl relative top-0.5'></span> ".$allStatus[$status];
    }
}

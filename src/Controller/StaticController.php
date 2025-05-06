<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'static_')]
class StaticController extends AbstractController
{
    #[Route('/statusi-kod-ostecenih-prosvetnih-radnika', name: 'damaged_educator_status')]
    public function damagedEducatorStatus(): Response
    {
        return $this->render('static/damaged_educator_status.html.twig');
    }

    #[Route('/statusi-kod-instrukcija-za-uplatu', name: 'transaction_status')]
    public function transactionStatus(): Response
    {
        return $this->render('static/transaction_status.html.twig');
    }
}

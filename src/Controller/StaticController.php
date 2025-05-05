<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'static_')]
class StaticController extends AbstractController
{
    #[Route('/statusi-kod-instrukcija-za-uplatu', name: 'transaction_status')]
    public function index(): Response
    {
        return $this->render('static/transaction_status.html.twig');
    }
}

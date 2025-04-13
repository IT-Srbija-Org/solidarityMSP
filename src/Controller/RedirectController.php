<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RedirectController extends AbstractController
{
    #[Route('/hvalaDonatoru')]
    #[Route('/hvalaDelegatu')]
    #[Route('/hvalaZaOstecenog')]
    #[Route('/obrazacDonatori')]
    #[Route('/obrazacDelegati')]
    #[Route('/profileDelegat')]
    #[Route('/obrazacOsteceni')]
    public function redirectToHome(): Response
    {
        return $this->redirectToRoute('home', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}

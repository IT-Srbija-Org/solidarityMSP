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
    public function redirectToHome(): Response
    {
        return $this->redirectToRoute('home', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/obrazacDonatori')]
    public function redirectDonor(): Response
    {
        return $this->redirectToRoute('donor_subscribe', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/obrazacDelegati')]
    #[Route('/profileDelegat')]
    #[Route('/obrazacOsteceni')]
    public function redirectDelegate(): Response
    {
        return $this->redirectToRoute('delegate_request', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}

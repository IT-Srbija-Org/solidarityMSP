<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route(path: '/logovanje', name: 'login')]
    public function login(Request $request, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->getPayload()->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user && $user->isActive() && $user->isVerified()) {
                $userRepository->sendLoginLink($user);

                $this->addFlash('success', 'Link za prijavu je poslat na vašu email adresu.');

                return $this->redirectToRoute('login');
            }

            if ($user && !$user->isActive()) {
                $this->addFlash('error', 'Korisnik sa ovom email adresom nije aktivan i ne može da se uloguje.');

                return $this->redirectToRoute('login');
            }

            if ($user && !$user->isVerified()) {
                $this->addFlash('error', 'Korisnik sa ovom email adresom nije verifikovan. Molimo da se verifikujete.');

                return $this->redirectToRoute('login');
            }

            $this->addFlash('error', 'Korisnik sa ovom email adresom ne postoji. Molimo da se registrujete.');

            return $this->redirectToRoute('login');
        }

        return $this->render('security/login.html.twig');
    }

    #[Route('/login_check', name: 'login_check')]
    public function check(): never
    {
        throw new \LogicException('This code should never be reached');
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}

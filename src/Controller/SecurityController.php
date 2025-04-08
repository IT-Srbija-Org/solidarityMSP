<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/logovanje', name: 'login')]
    public function login(Request $request, LoginLinkHandlerInterface $loginLinkHandler, MailerInterface $mailer, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->getPayload()->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $loginLinkDetails = $loginLinkHandler->createLoginLink($user);
                $loginLink = $loginLinkDetails->getUrl();

                $message = (new TemplatedEmail())
                    ->to($user->getEmail())
                    ->subject('Link za prijavu')
                    ->htmlTemplate('security/login_link_email.html.twig')
                    ->context(['link' => $loginLink]);

                $mailer->send($message);
                $this->addFlash('success', 'Link za prijavu je poslat na vašu email adresu.');
            } else {
                $this->addFlash('error', 'Korisnik sa ovom email adresom ne postoji. Molimo da se registrujete.');
            }
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

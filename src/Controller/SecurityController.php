<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/logovanje', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/zaboravljena-lozinka', name: 'forgot_password')]
    public function forgotPassword(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $user->setResetToken(md5(uniqid(microtime(true), true)));
                $user->setResetTokenCreatedAt(new \DateTimeImmutable());
                $entityManager->flush();

                // Generate reset link
                $resetLink = $this->generateUrl('change_password', [
                    'token' => $user->getResetToken(),
                    'id' => $user->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $message = (new TemplatedEmail())
                    ->to($user->getEmail())
                    ->subject('Link za promenu lozinke')
                    ->htmlTemplate('security/reset_password_email.html.twig')
                    ->context([
                        'link' => $resetLink
                    ]);

                $mailer->send($message);
            }

            $this->addFlash('success', 'Link za restratovanje lozinke je poslat.');
            return $this->redirectToRoute('login');
        }

        return $this->render('security/forget_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/promena-lozinke/{id}/{token}', name: 'change_password')]
    public function changePassword(int $id, string $token, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $userRepository->find($id);

        if (!$user || $user->getResetToken() !== $token) {
            $this->addFlash('error', 'Neispravan link za restartovanje lozinke.');
            return $this->redirectToRoute('forgot_password');
        }

        $createdAt = $user->getResetTokenCreatedAt();
        if (!$createdAt || (new \DateTimeImmutable())->getTimestamp() - $createdAt->getTimestamp() > 3600) {
            $this->addFlash('error', 'Link je istekao. Morate ponovo poslati zahtev za restartovanje.');
            return $this->redirectToRoute('forgot_password');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('rawPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $password));

            $user->setResetToken(null);
            $user->setResetTokenCreatedAt(null);
            $entityManager->flush();

            $this->addFlash('success', 'Lozinka je uspesno promenjena.');
            return $this->redirectToRoute('login');
        }

        return $this->render('security/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

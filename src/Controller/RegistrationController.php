<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Repository\UserDonorRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/registracija', name: 'register')]
    public function register(Request $request, UserRepository $userRepository): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $userRepository->sendVerificationLink($user, null);

            return $this->redirectToRoute('verify_email_send');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/verifikacija-email', name: 'verify_email_send')]
    public function verifyEmailSend(): Response
    {
        return $this->render('registration/verify_email_send.html.twig');
    }

    #[Route('/uspesna-verifikacija-emaila', name: 'verify_email_success')]
    public function verifyEmailSuccess(): Response
    {
        return $this->render('registration/verify_email_success.html.twig');
    }

    #[Route('/email-verifikacija', name: 'verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository, UserDonorRepository $userDonorRepository, Security $security): Response
    {
        $userId = $request->get('id');
        if (!$userId) {
            throw new AuthenticationCredentialsNotFoundException();
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            throw new AuthenticationCredentialsNotFoundException();
        }

        try {
            // Check if user validation token valid
            $this->emailVerifier->handleEmailConfirmation($request, $user);

            // Login user
            $security->login($user, 'form_login');

            $action = $request->get('action');
            if ('donor' == $action) {
                $userDonorRepository->sendSuccessEmail($user);

                return $this->redirectToRoute('donor_success');
            }

            if ('delegate' == $action) {
                return $this->redirectToRoute('delegate_request_success');
            }
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $exception->getReason());

            return $this->redirectToRoute('register');
        }

        return $this->redirectToRoute('verify_email_success');
    }
}

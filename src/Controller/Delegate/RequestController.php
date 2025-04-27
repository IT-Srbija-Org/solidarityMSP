<?php

namespace App\Controller\Delegate;

use App\Entity\User;
use App\Entity\UserDelegateRequest;
use App\Form\RegistrationDelegateType;
use App\Repository\UserRepository;
use App\Service\CloudFlareTurnstileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'delegate_request_')]
class RequestController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/postani-delegat', name: 'form')]
    public function form(Request $request, UserRepository $userRepository, CloudFlareTurnstileService $cloudFlareTurnstileService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user && in_array('ROLE_DELEGATE', $user->getRoles())) {
            return $this->render('delegate/request/approved.html.twig');
        }

        if ($user && $user->getUserDelegateRequest() && UserDelegateRequest::STATUS_NEW != $user->getUserDelegateRequest()->getStatus()) {
            return $this->render('delegate/request/already_exist.html.twig');
        }

        $userDelegateRequest = new UserDelegateRequest();
        if ($user && $user->getUserDelegateRequest()) {
            $userDelegateRequest = $user->getUserDelegateRequest();
        }

        $form = $this->createForm(RegistrationDelegateType::class, $userDelegateRequest, [
            'user' => $user,
        ]);

        $form->handleRequest($request);
        if (!$user && $form->isSubmitted() && $form->isValid()) {
            $captchaToken = $request->getPayload()->get('cf-turnstile-response');
            if (!$cloudFlareTurnstileService->isValid($captchaToken)) {
                $form->addError(new FormError('Captcha nije validna.'));
            }
        }

        if (!$user && $form->isSubmitted() && $form->isValid()) {
            $firstName = $userDelegateRequest->getFirstName();
            $lastName = $userDelegateRequest->getLastName();
            $email = $form->get('email')->getData();

            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $form->get('email')->addError(new FormError('Korisnik sa ovom email adresom vec postoji, molimo Vas da se ulogujete i da nastavite proces.'));
                $userRepository->sendLoginLink($user);
            } elseif (preg_match('/edu\.rs$/i', $email)) {
                $form->get('email')->addError(new FormError('Nije dozvoljeno korišćenje email adresa "edu.rs" zbog bezbednosnih razloga. Molimo vas da unesete Vašu ličnu email adresu.'));
            } else {
                $user = $userRepository->createUser($firstName, $lastName, $email);
                $userRepository->sendVerificationLink($user, 'delegate');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $userDelegateRequest->setUser($user);
            $this->entityManager->persist($userDelegateRequest);

            $this->entityManager->flush();

            return $this->redirectToRoute('delegate_request_success');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Došlo je do greške, molimo Vas da proverite unešene podatke.');
        }

        return $this->render('delegate/request/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/uspesna-registracija-delegata', name: 'success')]
    public function messageSuccess(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user && $user->isEmailVerified()) {
            return $this->render('delegate/request/success.html.twig');
        }

        return $this->render('delegate/request/success_need_verify.html.twig');
    }
}

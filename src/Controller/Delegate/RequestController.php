<?php

namespace App\Controller\Delegate;

use App\Entity\User;
use App\Entity\UserDelegateRequest;
use App\Form\RegistrationDelegateType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'delegate_')]
class RequestController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/postani-delegat', name: 'request')]
    public function request(Request $request, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user && in_array('ROLE_DELEGATE', $user->getRoles())) {
            return $this->render('delegate/request_approved.html.twig');
        }

        if ($user && $user->getUserDelegateRequest() && UserDelegateRequest::STATUS_NEW != $user->getUserDelegateRequest()->getStatus()) {
            return $this->render('delegate/request_already_exist.html.twig');
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
            $firstName = $form->get('firstName')->getData();
            $lastName = $form->get('lastName')->getData();
            $email = $form->get('email')->getData();

            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $form->get('email')->addError(new FormError('Korisnik sa ovom email adresom vec postoji, molimo Vas da se ulogujete i da nastavite proces.'));
                $userRepository->sendLoginLink($user);
            } else {
                $user = $userRepository->createUser($firstName, $lastName, $email);
                $userRepository->sendVerificationLink($user, 'delegate');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $firstName = $form->get('firstName')->getData();
            $lastName = $form->get('lastName')->getData();

            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $this->entityManager->persist($user);

            $userDelegateRequest->setUser($user);
            $this->entityManager->persist($userDelegateRequest);

            $this->entityManager->flush();

            return $this->redirectToRoute('delegate_request_success');
        }

        return $this->render('delegate/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/uspesna-registracija-delegata', name: 'request_success')]
    public function messageSuccess(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user && $user->isVerified()) {
            return $this->render('delegate/request_success.html.twig');
        }

        return $this->render('delegate/request_success_need_verify.html.twig');
    }
}

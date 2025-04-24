<?php

namespace App\Controller\Donor;

use App\Entity\UserDonor;
use App\Form\UserDonorType;
use App\Repository\UserDonorRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(name: 'donor_request_')]
class RequestController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/postani-donator', name: 'form')]
    public function form(Request $request, UserRepository $userRepository, UserDonorRepository $userDonorRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $userDonor = new UserDonor();
        if ($user && $user->getUserDonor()) {
            $userDonor = $user->getUserDonor();
        }

        $form = $this->createForm(UserDonorType::class, $userDonor, [
            'user' => $user,
        ]);

        $form->handleRequest($request);
        if (!$user && $form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $form->get('email')->addError(new FormError('Korisnik sa ovom email adresom vec postoji, molimo Vas da se ulogujete i da nastavite proces.'));
                $userRepository->sendLoginLink($user);
            } else {
                $user = $userRepository->createUser(null, null, $email);
                $userRepository->sendVerificationLink($user, 'donor');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = !$userDonor->getId();

            $userDonor->setUser($user);
            $this->entityManager->persist($userDonor);
            $this->entityManager->flush();

            if ($isNew && $user->isEmailVerified()) {
                $userDonorRepository->sendSuccessEmail($user);
            }

            return $this->redirectToRoute('donor_success');
        }

        return $this->render('donor/request/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/uspesna-registracija-donatora', name: 'success')]
    public function messageSuccess(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user && $user->isEmailVerified()) {
            return $this->render('donor/request/success.html.twig');
        }

        return $this->render('donor/request/success_need_verify.html.twig');
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/odjava-donatora', name: 'unsubscribe')]
    public function unsubscribe(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('unsubscribe', $request->query->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $userDonor = $user->getUserDonor();

        if ($userDonor) {
            $this->entityManager->remove($userDonor);
            $this->entityManager->flush();
        }

        $this->addFlash('success', 'Uspešno ste se odjavili sa liste donora');

        return $this->redirectToRoute('donor_form');
    }
}

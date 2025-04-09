<?php

namespace App\Controller;

use App\Entity\UserDonor;
use App\Form\UserDonorType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route(name: 'donor_')]
class DonorController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/postani-donator', name: 'subscribe')]
    public function subscribe(Request $request, MailerInterface $mailer): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $userDonor = $user->getUserDonor() ?? new UserDonor();
        $userDonor->setUser($user);

        $form = $this->createForm(UserDonorType::class, $userDonor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = !$userDonor->getId();
            $this->entityManager->persist($userDonor);
            $this->entityManager->flush();

            if ($isNew) {
                $message = (new TemplatedEmail())
                    ->to($user->getEmail())
                    ->subject('Potvrda registracije donora na Mrežu solidarnosti')
                    ->htmlTemplate('donor/success_email.html.twig');

                $mailer->send($message);
            }

            return $this->redirectToRoute('donor_success');
        }

        return $this->render('donor/subscribe.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/uspesna-registracija-donatora', name: 'success')]
    public function messageSuccessSupport(): Response
    {
        return $this->render('donor/success.html.twig');
    }

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

        return $this->redirectToRoute('donor_subscribe');
    }
}

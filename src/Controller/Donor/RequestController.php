<?php

namespace App\Controller\Donor;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\UserDonor;
use App\Form\UserDonorRegister;
use App\Form\UserDonorSubscription;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Service\CloudFlareTurnstileService;
use App\Service\CreateTransactionService;
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
    public function __construct(private EntityManagerInterface $entityManager, private TransactionRepository $transactionRepository, private UserRepository $userRepository, private CreateTransactionService $createTransactionService, private CloudFlareTurnstileService $cloudFlareTurnstileService)
    {
    }

    #[Route('/doniraj', name: 'donate')]
    public function donate(): Response
    {
        return $this->render('donor/request/donate.html.twig');
    }

    #[Route('/registracija-donatora', name: 'register')]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('donor_request_donate');
        }

        /** @var User $user */
        $user = new User();
        $action = $request->query->get('action');

        $form = $this->createForm(UserDonorRegister::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $captchaToken = $request->getPayload()->get('cf-turnstile-response');
            if (!$this->cloudFlareTurnstileService->isValid($captchaToken)) {
                $form->addError(new FormError('Captcha nije validna.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->userRepository->sendVerificationLink($user, $action);

            return $this->redirectToRoute('donor_request_success', [
                'action' => $action,
            ]);
        }

        return $this->render('donor/request/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/jednokratna-donacija', name: 'onetime')]
    #[Route('/mesecna-donacija', name: 'subscription')]
    public function options(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $routeName = $request->attributes->get('_route');

        if (!$user) {
            return $this->redirectToRoute('donor_request_register', [
                'action' => $routeName,
            ]);
        }

        if ('donor_request_onetime' == $routeName) {
            return $this->redirectToRoute('donor_transaction_create');
        }

        $userDonor = new UserDonor();
        $isNew = true;

        if ($user->getUserDonor()) {
            $userDonor = $user->getUserDonor();
            $isNew = false;
        }

        $form = $this->createForm(UserDonorSubscription::class, $userDonor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userDonor->setUser($user);
            $userDonor->setIsMonthly(true);
            $this->entityManager->persist($userDonor);
            $this->entityManager->flush();

            if ($isNew) {
                $haveWaitingTransactions = $this->transactionRepository->count([
                    'user' => $user,
                    'status' => Transaction::STATUS_NEW,
                ]);

                if (!$haveWaitingTransactions) {
                    $this->createTransactionService->create($user, $userDonor->getAmount(), $userDonor->getSchoolType());
                }

                return $this->redirectToRoute('donor_transaction_list');
            }

            $this->addFlash('success', 'Uspesno ste promenili podatke');
        }

        return $this->render('donor/request/subscription.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/uspesna-registracija-donatora', name: 'success')]
    public function messageSuccess(Request $request): Response
    {
        $action = $request->query->get('action');
        if ('donor_request_register' == $action) {
            return $this->render('donor/request/success_need_verify.html.twig');
        }

        if ('donor_request_subscription' == $action) {
            return $this->render('donor/request/success_subscription.html.twig');
        }

        return $this->render('donor/request/success_onetime.html.twig');
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/odjava-mesecnog-donatora', name: 'unsubscribe')]
    public function unsubscribe(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('unsubscribe', $request->query->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $userDonor = $user->getUserDonor();

        if ($userDonor) {
            $this->entityManager->remove($userDonor);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('donor_request_donate');
    }
}

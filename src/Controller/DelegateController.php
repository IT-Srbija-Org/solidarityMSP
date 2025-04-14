<?php

namespace App\Controller;

use App\Entity\DamagedEducator;
use App\Entity\User;
use App\Entity\UserDelegateRequest;
use App\Form\ConfirmType;
use App\Form\DamagedEducatorEditType;
use App\Form\DamagedEducatorSearchType;
use App\Form\RegistrationDelegateType;
use App\Repository\DamagedEducatorPeriodRepository;
use App\Repository\DamagedEducatorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(name: 'delegate_')]
class DelegateController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/postani-delegat', name: 'request_access')]
    public function requestAccess(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (in_array('ROLE_DELEGATE', $user->getRoles())) {
            return $this->render('delegate/request_approved.html.twig');
        }

        if ($user->getUserDelegateRequest() && UserDelegateRequest::STATUS_NEW != $user->getUserDelegateRequest()->getStatus()) {
            return $this->render('delegate/request_already_exist.html.twig');
        }

        $userDelegateRequest = $user->getUserDelegateRequest() ?? new UserDelegateRequest();
        $userDelegateRequest->setUser($user);

        $form = $this->createForm(RegistrationDelegateType::class, $userDelegateRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($userDelegateRequest);
            $this->entityManager->flush();

            return $this->redirectToRoute('delegate_request_access');
        }

        return $this->render('delegate/request_access.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_DELEGATE')]
    #[Route('/osteceni-period', name: 'damaged_educator_period')]
    public function damagedEducatorPeriod(DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository): Response
    {
        $items = $damagedEducatorPeriodRepository->findBy([], [
            'year' => 'DESC',
            'month' => 'DESC',
        ]);

        return $this->render('delegate/damaged_educator_period.html.twig', [
            'items' => $items,
        ]);
    }

    #[IsGranted('ROLE_DELEGATE')]
    #[Route('/osteceni', name: 'damaged_educators')]
    public function damagedEducators(Request $request, DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository, DamagedEducatorRepository $damagedEducatorRepository): Response
    {
        $periodId = $request->query->getInt('period');
        $period = $damagedEducatorPeriodRepository->find($periodId);
        if (empty($period)) {
            return $this->redirectToRoute('delegate_damaged_educator_period');
        }

        $form = $this->createForm(DamagedEducatorSearchType::class, null, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);
        $criteria = [];

        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        /** @var User $user */
        $user = $this->getUser();

        $criteria['schools'] = [];
        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $criteria['schools'][] = $delegateSchool->getSchool()->getId();
        }

        $criteria['period'] = $period;
        $page = $request->query->getInt('page', 1);

        return $this->render('delegate/damaged_educators.html.twig', [
            'damagedEducators' => $damagedEducatorRepository->search($criteria, $page),
            'period' => $period,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_DELEGATE')]
    #[Route('/prijavi-ostecenog', name: 'new_damaged_educator')]
    public function newDamagedEducator(Request $request, DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository): Response
    {
        $periodId = $request->query->getInt('period');
        $period = $damagedEducatorPeriodRepository->find($periodId);
        if (empty($period) || !$period->isActive()) {
            throw $this->createAccessDeniedException();
        }

        $damagedEducator = new DamagedEducator();
        $damagedEducator->setCreatedBy($this->getUser());
        $damagedEducator->setPeriod($period);

        $form = $this->createForm(DamagedEducatorEditType::class, $damagedEducator, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($damagedEducator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste sačuvali oštećenog.');

            return $this->redirectToRoute('delegate_damaged_educators', [
                'period' => $damagedEducator->getPeriod()->getId(),
            ]);
        }

        return $this->render('delegate/edit_damaged_educator.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
        ]);
    }

    #[IsGranted('ROLE_DELEGATE')]
    #[Route('/osteceni/{id}/izmeni-podatke', name: 'edit_damaged_educator')]
    public function editDamagedEducator(Request $request, DamagedEducator $damagedEducator): Response
    {
        if (!$damagedEducator->getPeriod()->isActive()) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $allowedSchools = [];
        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $allowedSchools[] = $delegateSchool->getSchool()->getId();
        }

        if (!in_array($damagedEducator->getSchool()->getId(), $allowedSchools)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(DamagedEducatorEditType::class, $damagedEducator, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $damagedEducator->setCreatedBy($this->getUser());
            $this->entityManager->persist($damagedEducator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste izmenili podatke od oštećenog.');

            return $this->redirectToRoute('delegate_damaged_educators', [
                'period' => $damagedEducator->getPeriod()->getId(),
            ]);
        }

        return $this->render('delegate/edit_damaged_educator.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
        ]);
    }

    #[IsGranted('ROLE_DELEGATE')]
    #[Route('/osteceni/{id}/brisanje', name: 'delete_damaged_educator')]
    public function deleteDamagedEducator(Request $request, DamagedEducator $damagedEducator): Response
    {
        if (!$damagedEducator->getPeriod()->isActive()) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $allowedSchools = [];
        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $allowedSchools[] = $delegateSchool->getSchool()->getId();
        }

        if (!in_array($damagedEducator->getSchool()->getId(), $allowedSchools)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ConfirmType::class, null, [
            'message' => 'Potvrđujem da želim da obrišem oštećenog "'.$damagedEducator->getName().'".',
            'submit_message' => 'Potvrdi',
            'submit_class' => 'btn btn-error',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($damagedEducator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste obrisali oštećenog.');

            return $this->redirectToRoute('delegate_damaged_educators', [
                'period' => $damagedEducator->getPeriod()->getId(),
            ]);
        }

        return $this->render('delegate/delete_damaged_educator.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
        ]);
    }
}

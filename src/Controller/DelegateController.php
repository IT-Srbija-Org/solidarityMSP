<?php

namespace App\Controller;

use App\Entity\Educator;
use App\Entity\User;
use App\Entity\UserDelegateRequest;
use App\Form\ConfirmType;
use App\Form\EducatorEditType;
use App\Form\EducatorSearchType;
use App\Form\RegistrationDelegateType;
use App\Repository\EducatorRepository;
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
    #[Route('/osteceni', name: 'educators')]
    public function educators(Request $request, EducatorRepository $educatorRepository): Response
    {
        $form = $this->createForm(EducatorSearchType::class, null, [
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

        $page = $request->query->getInt('page', 1);

        return $this->render('delegate/educators.html.twig', [
            'educators' => $educatorRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_DELEGATE')]
    #[Route('/prijavi-ostecenog', name: 'new_educator')]
    public function newEducator(Request $request): Response
    {
        $educator = new Educator();
        $educator->setCreatedBy($this->getUser());

        $form = $this->createForm(EducatorEditType::class, $educator, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($educator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste sačuvali oštećenog.');

            return $this->redirectToRoute('delegate_educators');
        }

        return $this->render('delegate/edit_educator.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_DELEGATE')]
    #[Route('/osteceni/{id}/izmeni-podatke', name: 'edit_educator')]
    public function editEducator(Request $request, Educator $educator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $allowedSchools = [];
        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $allowedSchools[] = $delegateSchool->getSchool()->getId();
        }

        if (!in_array($educator->getSchool()->getId(), $allowedSchools)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EducatorEditType::class, $educator, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $educator->setCreatedBy($this->getUser());
            $this->entityManager->persist($educator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste izmenili podatke od oštećenog.');

            return $this->redirectToRoute('delegate_educators');
        }

        return $this->render('delegate/edit_educator.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_DELEGATE')]
    #[Route('/osteceni/{id}/brisanje', name: 'delete_educator')]
    public function deleteEducator(Request $request, Educator $educator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $allowedSchools = [];
        foreach ($user->getUserDelegateSchools() as $delegateSchool) {
            $allowedSchools[] = $delegateSchool->getSchool()->getId();
        }

        if (!in_array($educator->getSchool()->getId(), $allowedSchools)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ConfirmType::class, null, [
            'message' => 'Potvrđujem da želim da obrišem oštećenog "'.$educator->getName().'".',
            'submit_message' => 'Potvrdi',
            'submit_class' => 'btn btn-error',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($educator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste obrisali oštećenog.');

            return $this->redirectToRoute('delegate_educators');
        }

        return $this->render('confirm_message.html.twig', [
            'iconClass' => 'ti ti-trash',
            'title' => 'Brisanje oštećenog',
            'backRouteName' => 'delegate_educators',
            'form' => $form->createView(),
        ]);
    }
}

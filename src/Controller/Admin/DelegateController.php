<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\UserDelegateSchool;
use App\Form\Admin\DelegateSearchType;
use App\Form\Admin\UserDelegateSchoolConnectType;
use App\Form\ConfirmType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/delegate', name: 'admin_delegate_')]
final class DelegateController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/list', name: 'list')]
    public function list(Request $request, UserRepository $userRepository): Response
    {
        $form = $this->createForm(DelegateSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $criteria['role'] = 'ROLE_DELEGATE';
        $criteria['isActive'] = true;

        $page = $request->query->getInt('page', 1);

        return $this->render('admin/delegate/list.html.twig', [
            'delegates' => $userRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/connect-school', name: 'connect_school')]
    public function connectSchool(Request $request, User $user): Response
    {
        if (!in_array('ROLE_DELEGATE', $user->getRoles())) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->isActive()) {
            throw $this->createAccessDeniedException();
        }

        $userDelegateSchool = new UserDelegateSchool();
        $userDelegateSchool->setUser($user);

        $form = $this->createForm(UserDelegateSchoolConnectType::class, $userDelegateSchool);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($userDelegateSchool);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste odvezali školu od delegata.');

            return $this->redirectToRoute('admin_delegate_connect_school', ['id' => $user->getId()]);
        }

        return $this->render('admin/delegate/connect_school.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/unconect-school', name: 'unconnect_school')]
    public function unconnectSchool(Request $request, User $user): Response
    {
        if (!in_array('ROLE_DELEGATE', $user->getRoles())) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->isActive()) {
            throw $this->createAccessDeniedException();
        }

        $userDelegateSchoolId = $request->get('user-delegate-school-id');
        if (!$userDelegateSchoolId) {
            throw $this->createNotFoundException();
        }

        $userDelegateSchool = $this->entityManager->getRepository(UserDelegateSchool::class)->find($userDelegateSchoolId);
        if (!$userDelegateSchool) {
            throw $this->createNotFoundException();
        }

        if ($userDelegateSchool->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ConfirmType::class, null, [
            'message' => 'Potvrđujem da želim da odvežem školu od zadatog delegata',
            'submit_message' => 'Potvrdi',
            'submit_class' => 'btn btn-error',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($userDelegateSchool);
            $this->entityManager->flush();

            $this->addFlash('success', 'Uspešno ste odvezali školu od delegata.');

            return $this->redirectToRoute('admin_delegate_connect_school', ['id' => $user->getId()]);
        }

        return $this->render('confirm_message.html.twig', [
            'iconClass' => 'link-off',
            'title' => 'Odvezivanje škole od delegata',
            'form' => $form->createView(),
        ]);
    }
}

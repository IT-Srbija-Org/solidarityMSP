<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\UserEditType;
use App\Form\Admin\UserSearchType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/user', name: 'admin_user_')]
final class UserController extends AbstractController
{
    #[Route('/list', name: 'list')]
    public function list(Request $request, UserRepository $userRepository): Response
    {
        $form = $this->createForm(UserSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);

        return $this->render('admin/user/list.html.twig', [
            'users' => $userRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Korisnički podaci su izmenjeni');

            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\UserDonor;
use App\Form\Admin\DonorEditType;
use App\Form\Admin\DonorSearchType;
use App\Form\ConfirmType;
use App\Repository\UserDonorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/donor', name: 'admin_donor_')]
final class DonorController extends AbstractController
{
    #[Route('/list', name: 'list')]
    public function list(Request $request, UserDonorRepository $userDonorRepository): Response
    {
        $form = $this->createForm(DonorSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);

        return $this->render('admin/donor/list.html.twig', [
            'donors' => $userDonorRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserDonor $userDonor): Response
    {
        $form = $this->createForm(DonorEditType::class, $userDonor, [
            'user' => $userDonor->getUser(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userDonor);
            $entityManager->flush();

            $this->addFlash('success', 'Uspešno ste izmenili podatke');

            return $this->redirectToRoute('admin_donor_list');
        }

        return $this->render('admin/donor/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete')]
    public function delete(Request $request, EntityManagerInterface $entityManager, UserDonor $userDonor): Response
    {
        $form = $this->createForm(ConfirmType::class, null, [
            'message' => 'Potvrđujem da želim da obrišem donatora',
            'submit_message' => 'Potvrdi',
            'submit_class' => 'btn btn-error',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->remove($userDonor);
            $entityManager->flush();

            $this->addFlash('success', 'Uspešno ste obrišali donatora');

            return $this->redirectToRoute('admin_donor_list');
        }

        return $this->render('admin/confirm_message.html.twig', [
            'iconClass' => 'trash',
            'title' => 'Brisanje donatora #'.$userDonor->getId(),
            'form' => $form->createView(),
        ]);
    }
}

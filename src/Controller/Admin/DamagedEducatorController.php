<?php

namespace App\Controller\Admin;

use App\Entity\DamagedEducator;
use App\Entity\Transaction;
use App\Form\Admin\DamagedEducatorEditType;
use App\Form\Admin\DamagedEducatorSearchType;
use App\Form\DamagedEducatorDeleteType;
use App\Repository\DamagedEducatorRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/damaged-educator', name: 'admin_damaged_educator_')]
final class DamagedEducatorController extends AbstractController
{
    #[Route('/list', name: 'list')]
    public function list(Request $request, DamagedEducatorRepository $damagedEducatorRepository): Response
    {
        $form = $this->createForm(DamagedEducatorSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);

        return $this->render('admin/damagedEducator/list.html.twig', [
            'damagedEducators' => $damagedEducatorRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/list-ajax', name: 'list_ajax')]
    public function index(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $periodId = $request->query->get('period-id');
        if (empty($periodId)) {
            return $this->json([]);
        }

        $schoolId = $request->query->get('school-id');
        if (empty($schoolId)) {
            return $this->json([]);
        }

        $damagedEducators = $entityManager->getRepository(DamagedEducator::class)->findBy([
            'period' => $periodId,
            'school' => $schoolId,
        ]);

        $items = [];
        foreach ($damagedEducators as $damagedEducator) {
            $items[] = [
                'id' => $damagedEducator->getId(),
                'name' => $damagedEducator->getName(),
                'accountNumber' => $damagedEducator->getAccountNumber(),
            ];
        }

        return $this->json($items);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, DamagedEducator $damagedEducator, EntityManagerInterface $entityManager): Response
    {
        if (!$damagedEducator->allowToEdit()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(DamagedEducatorEditType::class, $damagedEducator);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($damagedEducator);
            $entityManager->flush();

            $this->addFlash('success', 'Podaci su izmenjeni');

            return $this->redirectToRoute('admin_damaged_educator_list');
        }

        return $this->render('admin/damagedEducator/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete')]
    public function delete(Request $request, DamagedEducator $damagedEducator, EntityManagerInterface $entityManager, TransactionRepository $transactionRepository): Response
    {
        if (!$damagedEducator->allowToUnDelete()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(DamagedEducatorDeleteType::class, null, [
            'damagedEducator' => $damagedEducator,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $damagedEducator->setStatus(DamagedEducator::STATUS_DELETED);
            $damagedEducator->setStatusComment($data['comment']);
            $entityManager->flush();

            // Cancel transactions
            $transactionRepository->cancelAllTransactions($damagedEducator, 'Instrukcija za uplatu je otkazana pošto je oštećeni obrisan.', [Transaction::STATUS_NEW], true);

            $this->addFlash('success', 'Uspešno ste obrisali oštećenog.');

            return $this->redirectToRoute('admin_damaged_educator_list');
        }

        return $this->render('admin/damagedEducator/delete.html.twig', [
            'form' => $form->createView(),
            'damagedEducator' => $damagedEducator,
        ]);
    }

    #[Route('/{id}/undelete', name: 'undelete')]
    public function undeleteDamagedEducator(DamagedEducator $damagedEducator, EntityManagerInterface $entityManager): Response
    {
        if (!$damagedEducator->allowToUnDelete()) {
            throw $this->createAccessDeniedException();
        }

        $damagedEducator->setStatus(DamagedEducator::STATUS_NEW);
        $damagedEducator->setStatusComment(null);
        $entityManager->flush();

        $this->addFlash('success', 'Uspešno ste vratili obrisanog oštećenog nastavnika.');

        return $this->redirectToRoute('admin_damaged_educator_list');
    }
}

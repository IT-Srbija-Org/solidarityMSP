<?php

namespace App\Controller\Admin;

use App\Entity\DamagedEducator;
use App\Form\Admin\DamagedEducatorSearchType;
use App\Repository\DamagedEducatorRepository;
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
}

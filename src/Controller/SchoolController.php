<?php

namespace App\Controller;

use App\Entity\School;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SchoolController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/schools')]
    public function index(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $cityId = $request->query->get('city-id');
        if (empty($cityId)) {
            return $this->json([]);
        }

        $schools = $entityManager->getRepository(School::class)->findBy([
            'city' => $cityId,
        ]);

        if (empty($schools)) {
            return $this->json([]);
        }

        $items = [];
        foreach ($schools as $school) {
            $items[] = [
                'id' => $school->getId(),
                'name' => $school->getName(),
            ];
        }

        return $this->json($items);
    }
}

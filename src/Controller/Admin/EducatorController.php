<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\UserDelegateSchool;
use App\Form\Admin\DelegateSearchType;
use App\Form\Admin\EducatorSearchType;
use App\Form\Admin\UserDelegateSchoolConnectType;
use App\Form\ConfirmType;
use App\Repository\EducatorRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/educator', name: 'admin_educator_')]
final class EducatorController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/', name: 'list')]
    public function list(Request $request, EducatorRepository $educatorRepository): Response
    {
        $form = $this->createForm(EducatorSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);
        return $this->render('admin/educator/list.html.twig', [
            'educators' => $educatorRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }
}

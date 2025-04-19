<?php

namespace App\Controller\Admin;

use App\Form\Admin\LogSearchType;
use App\Repository\LogEntityChangeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/log', name: 'admin_log_')]
final class LogController extends AbstractController
{
    #[Route('/list', name: 'list')]
    public function list(Request $request, LogEntityChangeRepository $logEntityChangeRepository): Response
    {
        $form = $this->createForm(LogSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);

        return $this->render('admin/log/list.html.twig', [
            'logs' => $logEntityChangeRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }
}

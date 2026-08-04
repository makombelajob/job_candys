<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FreelanceApplicationController extends AbstractController
{
    #[Route('/freelance/application', name: 'app_freelance_application')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('freelance_application/index.html.twig', [
            'controller_name' => 'FreelanceApplicationController',
        ]);
    }
}

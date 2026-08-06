<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FindContactController extends AbstractController
{
    #[Route('/find/contact', name: 'app_find_contact')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER', 'ROLE_FREELANCE');
        return $this->render('find_contact/index.html.twig', [
            'controller_name' => 'FindContactController',
        ]);
    }
}

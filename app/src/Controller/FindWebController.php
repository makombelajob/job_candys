<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FindWebController extends AbstractController
{
    #[Route('/find/web', name: 'app_find_web')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('find_web/index.html.twig', [
            'controller_name' => 'FindWebController',
        ]);
    }
}

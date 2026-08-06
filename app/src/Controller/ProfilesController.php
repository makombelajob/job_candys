<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfilesController extends AbstractController
{
    #[Route('/profiles', name: 'app_profiles')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /**
         * All rest of code here and hope get it and again
         */
        return $this->render('profiles/index.html.twig', [
            'controller_name' => 'ProfilesController',
        ]);
    }
}

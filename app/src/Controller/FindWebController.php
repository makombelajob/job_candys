<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

final class FindWebController extends AbstractController
{
    #[Route('/find/web/{siret}/{nom}', name: 'app_find_web')]
    public function index(
        string $siret,
        string $nom
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $process = new Process([
            'python3',
            '/var/www/html/python/website_finder.py',
            $nom,

        ]);

        $process->run();

        $resultat = '';

        if ($process->isSuccessful()) {
            $resultat = trim($process->getOutput());
        } else {
            $resultat = trim($process->getErrorOutput());
        }

        return $this->render(
            'find_web/index.html.twig',
            [
                'nom' => $nom,
                'siret' => $siret,
                'resultat' => $resultat,
            ]
        );
    }
}
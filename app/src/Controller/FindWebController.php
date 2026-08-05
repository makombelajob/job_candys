<?php

namespace App\Controller;

use App\Service\InseeApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

final class FindWebController extends AbstractController
{
    public function __construct(
        private InseeApiService $inseeApiService
    ) {
    }


    #[Route('/find/web/{siret}', name: 'app_find_web')]
    public function index(string $siret): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /*
         * Transformation SIRET -> SIREN
         */
        $siren = substr($siret, 0, 9);

        /*
         * Récupération des informations INSEE
         */
        $entreprise = $this->inseeApiService->findBySiren($siren);


        /*
         * Récupération établissement
         */
        $etablissement = $entreprise['etablissements'][0] ?? [];

        /*
         * Nom entreprise
         */
        $nom = $etablissement['uniteLegale']['denominationUniteLegale']
            ?? '';

        /*
         * Construction adresse complète
         */
        $adresseData = $etablissement['adresseEtablissement'] ?? [];

        $adresse = trim(
            ($adresseData['numeroVoieEtablissement'] ?? '') . ' ' .
            ($adresseData['typeVoieEtablissement'] ?? '') . ' ' .
            ($adresseData['libelleVoieEtablissement'] ?? '')
        );

        $ville = trim(
            ($adresseData['codePostalEtablissement'] ?? '') . ' ' .
            ($adresseData['libelleCommuneEtablissement'] ?? '')
        );

        if ($ville !== '') {
            $adresse .= "\n" . $ville;
        }

        /*
         * Recherche du site avec Python
         */
        $process = new Process([
            #'/bin/python3',
            'python3',
            '/var/www/html/python/website_finder.py',
            $nom,
        ]);

        $process->run();

        if ($process->isSuccessful()) {
            $resultat = trim($process->getOutput());
        } else {
            $resultat = trim($process->getErrorOutput());
        }

        return $this->render(
            'find_web/index.html.twig',
            [
                'siret' => $siret,
                'nom' => $nom,
                'adresse' => $adresse,
                'resultat' => $resultat,
            ]
        );
    }
}
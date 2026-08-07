<?php

namespace App\Controller;

use App\Service\InseeApiService;
use App\Service\WebsiteFinderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Companies;
use App\Repository\CompaniesRepository;
use Doctrine\ORM\EntityManagerInterface;


final class FindWebController extends AbstractController
{
    public function __construct(
        private InseeApiService $inseeApiService,
        private WebsiteFinderService $websiteFinderService,
        private CompaniesRepository $companiesRepository,
        private EntityManagerInterface $entityManager,
    )
    {
        /**
         * Nothing to construct for now
         */
    }


    #[Route('/find/web/{siret}', name: 'app_find_web')]
    public function index(string $siret, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER', 'ROLE_FREELANCE');


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
         * Recherche du site avec le service PHP
         */
        $resultat = $this->websiteFinderService->findWebsite($nom);

        /**
         *  Saving in db
         */

        $website = (!empty($resultat) && $resultat !== 'Aucun site trouvé') ? $resultat : null;

        $company = $this->companiesRepository->findOneBy(['siret' => $siret]);
        dd($company);

        if(!$company){
            $company = new Companies();
            $company->setSiret($siret);
            $company->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($company);
        }
        $company->setFullName($nom);
        $company->setAddress($adresse);
        $company->setWebSite($website);
        $company->setLastCheck(new \DateTimeImmutable());
        $company->setUpdatedAt(new \DateTimeImmutable());

        $company->addUser($this->getUser());

        $this->entityManager->flush();

        return $this->render(
            'find_web/index.html.twig',
            [
                'siret' => $siret,
                'nom' => $nom,
                'adresse' => $adresse,
                'resultat' => $resultat ?? 'Aucun site trouvé',
            ]
        );
    }
}

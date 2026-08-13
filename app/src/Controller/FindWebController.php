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
    ) {
    }

    #[Route('/find/web/{siret}', name: 'app_find_web')]
    public function index(string $siret, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER', 'ROLE_FREELANCE');

        $nom = '';
        $adresse = '';
        $resultat = null;
        $errorMessage = null;

        /*
         * ==========================================================
         * 1. ON COMMENCE PAR CHERCHER LE SIRET EN BDD
         * ==========================================================
         */
        $company = $this->companiesRepository->findOneBy([
            'siret' => $siret
        ]);

        /*
         * ==========================================================
         * 2. SI LE SITE EXISTE EN BDD
         * ==========================================================
         *
         * On utilise directement celui de la BDD.
         * Aucun appel au WebsiteFinderService.
         */
        if ($company && !empty($company->getWebSite())) {

            $nom = $company->getFullName() ?? '';
            $adresse = $company->getAddress() ?? '';
            $resultat = $company->getWebSite();

        } else {

            /*
             * ======================================================
             * 3. PAS DE SITE EN BDD
             *
             * On récupère les informations INSEE.
             * ======================================================
             */
            try {

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
                $nom = $etablissement['uniteLegale']['denominationUniteLegale'] ?? '';

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
                 * ==================================================
                 * 4. RECHERCHE DU SITE
                 *
                 * Uniquement parce que la BDD n'en possède pas.
                 * ==================================================
                 */
                if (!empty($nom)) {

                    try {

                        $resultat = $this->websiteFinderService->findWebsite($nom);

                    } catch (\InvalidArgumentException $e) {

                        $errorMessage = 'Erreur : ' . $e->getMessage();

                    } catch (\Exception $e) {

                        $errorMessage = 'Une erreur s\'est produite lors de la recherche du site web.';
                    }
                }

                /*
                 * ==================================================
                 * 5. SAUVEGARDE / CREATION EN BDD
                 * ==================================================
                 */
                if (!empty($nom)) {

                    /*
                     * Si l'entreprise n'existe pas encore,
                     * on la crée.
                     */
                    if (!$company) {

                        $company = new Companies();

                        $company->setSiret($siret);
                        $company->setCreatedAt(new \DateTimeImmutable());

                        $this->entityManager->persist($company);
                    }

                    $company->setFullName($nom);
                    $company->setAddress($adresse);

                    /*
                     * On sauvegarde le site seulement si
                     * le service en a trouvé un.
                     */
                    if (!empty($resultat)) {
                        $company->setWebSite($resultat);
                    }

                    $company->setLastCheck(new \DateTimeImmutable());
                    $company->setUpdatedAt(new \DateTimeImmutable());

                    $company->addUser($this->getUser());

                    $this->entityManager->flush();
                }

            } catch (\Exception $e) {

                $errorMessage =
                    'Impossible de récupérer les informations de l\'entreprise. Vérifiez le SIRET saisi.';
            }
        }

        /*
         * ==========================================================
         * 6. AFFICHAGE
         * ==========================================================
         */
        return $this->render(
            'find_web/index.html.twig',
            [
                'siret' => $siret,
                'nom' => $nom,
                'adresse' => $adresse,
                'resultat' => $resultat,
                'errorMessage' => $errorMessage,
            ]
        );
    }
}
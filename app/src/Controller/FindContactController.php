<?php

namespace App\Controller;

use App\Entity\Companies;
use App\Entity\CompanyContacts;
use App\Form\CompanyEditType;
use App\Repository\CompaniesRepository;
use App\Repository\CompanyContactsRepository;
use App\Service\WebsiteContactFinderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FindContactController extends AbstractController
{
    public function __construct(
        private CompaniesRepository $companiesRepository,
        private CompanyContactsRepository $companyContactsRepository,
        private WebsiteContactFinderService $websiteContactFinderService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/find/contact/{siret}', name: 'app_find_contact')]
    public function index(string $siret): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $emails = [];
        $errorMessage = null;
        $company = null;
        $website = null;
        $existingContacts = [];

        try {

            /*
             * ==========================================================
             * 1. RECHERCHE DE L'ENTREPRISE
             * ==========================================================
             */

            $company = $this->companiesRepository->findOneBy([
                'siret' => $siret,
            ]);

            if (!$company) {
                return $this->render('find_contact/index.html.twig', [
                    'company' => null,
                    'website' => null,
                    'emails' => null,
                    'errorMessage' => 'Entreprise introuvable.',
                    'hasContacts' => false,
                ]);
            }


            /*
             * ==========================================================
             * 2. RÉCUPÉRATION DU SITE
             * ==========================================================
             */

            $website = $company->getWebSite();

            if (!$website) {
                return $this->render('find_contact/index.html.twig', [
                    'company' => $company,
                    'website' => null,
                    'emails' => null,
                    'errorMessage' => 'Aucun site enregistré.',
                    'hasContacts' => false,
                ]);
            }


            /*
             * ==========================================================
             * 3. RECHERCHE DIRECTE DES CONTACTS EN BDD
             * ==========================================================
             *
             * On ne passe plus par :
             *
             * $company->getCompanyContacts()
             *
             * La BDD est directement interrogée via le repository.
             */

            $existingContacts = $this->companyContactsRepository
                ->findByCompany($company);


            /*
             * ==========================================================
             * 4. DES CONTACTS EXISTENT DÉJÀ
             * ==========================================================
             */

            if (count($existingContacts) > 0) {

                foreach ($existingContacts as $contact) {

                    $email = $contact->getEmail();

                    if ($email) {
                        $emails[] = $email;
                    }
                }


                /*
                 * ==========================================================
                 * 5. AUCUN CONTACT EN BDD
                 *
                 * On lance alors la recherche sur le site.
                 * ==========================================================
                 */

            } else {

                try {

                    $foundEmails = $this->websiteContactFinderService
                        ->findContacts($website) ?? [];

                    $emails = $foundEmails;


                    /*
                     * ==================================================
                     * 6. SAUVEGARDE DES CONTACTS TROUVÉS
                     * ==================================================
                     */

                    foreach ($emails as $email) {

                        $email = trim((string) $email);

                        if ($email === '') {
                            continue;
                        }

                        $contact = new CompanyContacts();

                        $contact->setEmail($email);
                        $contact->setCompany($company);

                        $this->entityManager->persist($contact);
                    }

                    $this->entityManager->flush();


                    /*
                     * ==================================================
                     * 7. ON RELIT LES CONTACTS DEPUIS LA BDD
                     *
                     * Important :
                     * après avoir créé les contacts, on actualise la
                     * variable afin que le bouton soit cohérent.
                     * ==================================================
                     */

                    $existingContacts = $this->companyContactsRepository
                        ->findByCompany($company);

                } catch (\InvalidArgumentException $e) {

                    $errorMessage = 'Erreur : ' . $e->getMessage();

                } catch (\Exception $e) {

                    $errorMessage =
                        'Une erreur s\'est produite lors de la recherche de contacts.';
                }
            }


        } catch (\Exception $e) {

            $errorMessage =
                'Une erreur s\'est produite. Veuillez réessayer.';
        }


        /*
         * ==========================================================
         * 8. LA BDD EST LA SOURCE DE VÉRITÉ POUR LE BOUTON
         * ==========================================================
         */

        $hasContacts = count($existingContacts) > 0;


        /*
         * ==========================================================
         * 9. AFFICHAGE
         * ==========================================================
         */

        return $this->render('find_contact/index.html.twig', [
            'company' => $company,
            'website' => $website,
            'emails' => !empty($emails) ? $emails : null,
            'errorMessage' => $errorMessage,
            'hasContacts' => $hasContacts,
        ]);
    }


    #[Route('/edit/contact/{siret}', name: 'app_edit_contact')]
    public function edit(
        string $siret,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');


        /*
         * ==========================================================
         * 1. RECHERCHE DE L'ENTREPRISE
         * ==========================================================
         */

        $company = $this->companiesRepository->findOneBy([
            'siret' => $siret,
        ]);

        if (!$company) {
            throw $this->createNotFoundException(
                'Entreprise introuvable.'
            );
        }


        /*
         * ==========================================================
         * 2. RECHERCHE DIRECTE DU CONTACT
         * ==========================================================
         */

        $contact = $this->companyContactsRepository->findOneBy([
            'company' => $company,
        ]);


        /*
         * ==========================================================
         * 3. FORMULAIRE
         * ==========================================================
         */

        $form = $this->createForm(
            CompanyEditType::class,
            $company
        );


        /*
         * ==========================================================
         * 4. PRÉREMPLISSAGE
         * ==========================================================
         */

        if ($contact) {
            $form->get('email')->setData(
                $contact->getEmail()
            );
        }


        $form->handleRequest($request);


        /*
         * ==========================================================
         * 5. ENREGISTREMENT
         * ==========================================================
         */

        if ($form->isSubmitted() && $form->isValid()) {

            $email = trim(
                (string) $form->get('email')->getData()
            );


            /*
             * On vérifie à nouveau directement en BDD.
             */

            $contact = $this->companyContactsRepository->findOneBy([
                'company' => $company,
            ]);


            /*
             * Contact existant
             */

            if ($contact) {

                $contact->setEmail($email);


                /*
                 * Nouveau contact
                 */

            } else {

                $contact = new CompanyContacts();

                $contact->setEmail($email);
                $contact->setCompany($company);

                $this->entityManager->persist($contact);
            }


            /*
             * Mise à jour de l'entreprise
             */

            $company->setUpdatedAt(
                new \DateTimeImmutable()
            );

            $this->entityManager->persist($company);

            $this->entityManager->flush();

            if ($this->isGranted('ROLE_FREELANCE')) {
                return $this->redirectToRoute(
                    'app_find_web',
                    [
                        'siret' => $company->getSiret(),
                    ]
                );
            }

            return $this->redirectToRoute(
                'app_spontaneous_application_send',
                [
                    'siret' => $company->getSiret(),
                ]
            );

            /*
             * Message de confirmation
             */

            $this->addFlash(
                'success',
                'Le contact a été enregistré avec succès.'
            );


            /*
             * Retour vers la candidature
             */

            return $this->redirectToRoute(
                'app_spontaneous_application_send',
                [
                    'siret' => $company->getSiret(),
                ]
            );
        }


        /*
         * ==========================================================
         * 6. AFFICHAGE DU FORMULAIRE
         * ==========================================================
         */

        return $this->render(
            'find_contact/edit_contact.html.twig',
            [
                'form' => $form->createView(),
                'company' => $company,
            ]
        );
    }
}

<?php

namespace App\Controller;

use App\Entity\CompanyContacts;
use App\Form\FreelancePropositionType;
use App\Form\SearchCompanyType;
use App\Repository\CompaniesRepository;
use App\Repository\CompanyContactsRepository;
use App\Service\CompanyTechnologyService;
use App\Service\InseeApiService;
use App\Service\WappalyzerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FreelanceApplicationController extends AbstractController
{
    #[Route('/freelance/application', name: 'app_freelance_application')]
    public function index(
        Request $request,
        InseeApiService $inseeApiService,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_FREELANCE');

        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        $form = $this->createForm(SearchCompanyType::class);

        $form->handleRequest($request);

        $resultats = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $motRecherche = $form->get('mot')->getData();

            if (!empty($motRecherche)) {

                $resultats = $inseeApiService->searchEntreprise(
                    $motRecherche
                );
            }
        }

        return $this->render(
            'freelance_application/index.html.twig',
            [
                'form' => $form->createView(),
                'resultats' => $resultats,
            ]
        );
    }

    #[Route('/check-technology/{siret}', name: 'app_freelance_check_tech')]
    public function checkWeb(
        string $siret,
        Request $request,
        CompaniesRepository $companiesRepository,
        WappalyzerService $wappalyzerService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_FREELANCE');

        if (!$siret) {
            throw $this->createNotFoundException('SIRET manquant.');
        }

        $company = $companiesRepository->findOneBy([
            'siret' => $siret,
        ]);

        if (!$company) {
            throw $this->createNotFoundException('Entreprise introuvable.');
        }

        $website = $company->getWebsite();

        if (!$website) {
            throw $this->createNotFoundException(
                'Aucun site web enregistré pour cette entreprise.'
            );
        }

        $analysis = $wappalyzerService->analyze($website);

        return $this->render(
            'freelance_application/check_tech.html.twig',
            [
                'company' => $company,
                'siret' => $company->getSiret(),
                'website' => $website,
                'analysis' => $analysis,
            ]
        );
    }

    #[Route('/proposition/{siret}', name: 'app_freelance_proposition')]
    public function proposition(
        string $siret,
        Request $request,
        CompaniesRepository $companiesRepository,
        CompanyContactsRepository $companyContactsRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_FREELANCE');

        // Recherche de l'entreprise
        $company = $companiesRepository->findOneBy([
            'siret' => $siret,
        ]);

        if (!$company) {
            throw $this->createNotFoundException(
                'Entreprise introuvable.'
            );
        }

        // Récupération des contacts
        $contacts = $companyContactsRepository->findBy([
            'company' => $company,
        ]);

        // Préparation des choix d'emails
        $contactChoices = [];

        foreach ($contacts as $contact) {

            $email = trim(
                (string) $contact->getEmail()
            );

            if ($email !== '') {
                $contactChoices[$email] = $email;
            }
        }

        // Création du formulaire
        $form = $this->createForm(
            FreelancePropositionType::class,
            [
                'siret' => $company->getSiret(),
            ],
            [
                'contact_choices' => $contactChoices,
            ]
        );

        $form->handleRequest($request);

        // Traitement du formulaire
        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            $email = $data['email'];
            $subject = $data['subject'];
            $message = $data['message'];

            // Le traitement d'envoi sera ajouté ici.

            $this->addFlash(
                'success',
                'La proposition a été préparée avec succès.'
            );

            return $this->redirectToRoute(
                'app_freelance_proposition',
                [
                    'siret' => $company->getSiret(),
                ]
            );
        }

        return $this->render(
            'freelance_application/proposition.html.twig',
            [
                'form' => $form->createView(),
                'company' => $company,
                'siret' => $company->getSiret(),
            ]
        );
    }
}
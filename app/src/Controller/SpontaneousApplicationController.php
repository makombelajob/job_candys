<?php

namespace App\Controller;

use App\Form\SearchCompanyType;
use App\Repository\CompaniesRepository;
use App\Entity\Applications;
use App\Form\ApplicationType;
use App\Service\InseeApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\EmailService;
use Symfony\Component\Routing\Attribute\Route;

final class SpontaneousApplicationController extends AbstractController
{
    #[Route('/spontaneous/application', name: 'app_spontaneous_application')]
    public function index(
        Request $request,
        InseeApiService $inseeApiService
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_USER');

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
            'spontaneous_application/index.html.twig',
            [
                'form' => $form->createView(),
                'resultats' => $resultats,
            ]
        );
    }

    #[Route('/spontaneous/application/send/{siret}', name: 'app_spontaneous_application_send')]
    public function send(
        string $siret,
        Request $request,
        InseeApiService $inseeApiService,
        CompaniesRepository $companiesRepository,
        EmailService $emailService,
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_USER');
        /**
         * Retreive company
         */
        $company = $companiesRepository->findOneBy([ 'siret' => $siret, ]);
        if (!$company) {
            throw $this->createNotFoundException( 'Entreprise introuvable.' );
        }
        $contacts = $company->getCompanyContacts();

        $emails = [];

        foreach($contacts as $contact) {
            if ($contact->getEmail()) {
                $emails[] = $contact->getEmail();
            }
        }

        $emails = array_values(array_unique($emails));

        $application = new Applications();
        $profil = $this->getUser()->getProfils();
        $form = $this->createForm(ApplicationType::class, $application, [
            'contacts' => $contacts,
            'profilCv' => $profil?->getDefaultCv(),
            'profilLetter' => $profil?->getDefaultLetter(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contact = $form->get('contact')->getData();

            $email = null;

            if ($contact) {
                $email = $contact->getEmail();
            }

            // Aucun email disponible pour le contact
            if (!$email) {
                $this->addFlash(
                    'error',
                    'Le contact sélectionné ne possède pas d’adresse email.'
                );

                return $this->redirectToRoute(
                    'app_spontaneous_application',
                    ['siret' => $siret]
                );
            }

            $message = $form->get('message')->getData();
            $cv = $form->get('cv')->getData();
            $lettreMotivation = $form->get('lettreMotivation')->getData();

            $attachments = [];

            /**
             * CV
             */
            if ($cv) {
                $attachments[] = [
                    'path' => $cv->getPathname(),
                    'name' => $cv->getClientOriginalName(),
                ];
            }

            /**
             * Lettre de motivation
             */
            if ($lettreMotivation) {
                $attachments[] = [
                    'path' => $lettreMotivation->getPathname(),
                    'name' => $lettreMotivation->getClientOriginalName(),
                ];
            }

            /**
             * Envoi du mail
             */
            $emailService->send(
                from: $this->getUser()->getEmail(),
                to: $email,
                subject: 'Candidature spontanée',
                template: 'email',
                context: [
                    'message' => $message,
                    'company' => $company,
                    'contact' => $contact,
                    'user' => $this->getUser(),
                ],
                attachments: $attachments
            );

            $this->addFlash(
                'success',
                'Votre candidature a bien été envoyée.'
            );

            return $this->redirectToRoute(
                'app_spontaneous_application',
                ['siret' => $siret]
            );
        }

        return $this->render(
            'spontaneous_application/application.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

}

<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\SearchCompanyType;
use App\Repository\CompaniesRepository;
use App\Repository\ApplicationsRepository;
use App\Entity\Applications;
use App\Service\FileUploader;
use App\Service\ImapService;
use App\Form\ApplicationType;
use App\Service\InseeApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\EmailService;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;



final class SpontaneousApplicationController extends AbstractController
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

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

    #[Route(
        '/spontaneous/application/send/{siret}',
        name: 'app_spontaneous_application_send'
    )]
    public function send(
        string $siret,
        Request $request,
        CompaniesRepository $companiesRepository,
        ApplicationsRepository $applicationsRepository,
        EmailService $emailService,
        FileUploader $fileUploader,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /**
         * Utilisateur connecté
         *
         * @var Users $user
         */
        $user = $this->getUser();

        /**
         * Récupération de l'entreprise
         */
        $company = $companiesRepository->findOneBy([
            'siret' => $siret,
        ]);

        if (!$company) {
            throw $this->createNotFoundException(
                'Entreprise introuvable.'
            );
        }

        /**
         * Récupération du profil
         */
        $profil = $user->getProfils();

        if (!$profil) {
            $this->addFlash(
                'error',
                'Votre profil est introuvable.'
            );

            return $this->redirectToRoute(
                'app_spontaneous_application',
                ['siret' => $siret]
            );
        }

        /**
         * Vérification :
         * l'utilisateur a-t-il déjà envoyé une candidature
         * à cette entreprise ?
         */
        if (
            $applicationsRepository->hasApplicationForProfileAndCompany(
                $profil,
                $company
            )
        ) {
            $this->addFlash(
                'error',
                'Vous avez déjà envoyé une candidature à cette entreprise.'
            );

            return $this->redirectToRoute(
                'app_spontaneous_application',
                ['siret' => $siret]
            );
        }

        /**
         * Contacts de l'entreprise
         */
        $contacts = $company->getCompanyContacts();

        /**
         * Création de la candidature
         */
        $application = new Applications();

        $form = $this->createForm(
            ApplicationType::class,
            $application,
            [
                'contacts' => $contacts,
                'profilCv' => $profil->getDefaultCv(),
                'profilLetter' => $profil->getDefaultLetter(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /**
             * Contact sélectionné
             */
            $contact = $form->get('contact')->getData();

            $email = null;

            if ($contact) {
                $email = $contact->getEmail();
            }

            /**
             * Vérification de l'email du contact
             */
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

            /**
             * Fichiers sélectionnés depuis le profil
             */
            $defaultCv = $form->get('defaultCv')->getData();
            $defaultLetter = $form->get('defaultLetter')->getData();

            /**
             * Nouveaux fichiers uploadés
             */
            $uploadedCv = $form->get('cv')->getData();
            $uploadedLetter = $form->get('lettreMotivation')->getData();

            /**
             * Pièces jointes
             */
            $attachments = [];

            /**
             * Variables utilisées pour le stockage
             * de la candidature
             */
            $cvFilename = null;
            $letterFilename = null;

            /**
             * =========================
             * CV
             * =========================
             */
            if ($uploadedCv) {

                $cvFilename = $fileUploader->upload($uploadedCv);

                $attachments[] = [
                    'path' => $fileUploader->getPath($cvFilename),

                    // Nom visible par le destinataire
                    'name' => 'curriculum-vitae.pdf',
                ];

            } elseif ($defaultCv) {

                if (!$fileUploader->exists($defaultCv)) {

                    $this->addFlash(
                        'error',
                        'Le CV sélectionné dans votre profil est introuvable.'
                    );

                    return $this->redirectToRoute(
                        'app_spontaneous_application',
                        ['siret' => $siret]
                    );
                }

                $cvFilename = $defaultCv;

                $attachments[] = [
                    'path' => $fileUploader->getPath($defaultCv),

                    // Nom visible par le destinataire
                    'name' => 'curriculum-vitae.pdf',
                ];
            }

            /**
             * =========================
             * LETTRE DE MOTIVATION
             * =========================
             */
            if ($uploadedLetter) {

                $letterFilename = $fileUploader->upload(
                    $uploadedLetter
                );

                $attachments[] = [
                    'path' => $fileUploader->getPath(
                        $letterFilename
                    ),

                    // Nom visible par le destinataire
                    'name' => 'lettre-de-motivation.pdf',
                ];

            } elseif ($defaultLetter) {

                if (!$fileUploader->exists($defaultLetter)) {

                    $this->addFlash(
                        'error',
                        'La lettre sélectionnée dans votre profil est introuvable.'
                    );

                    return $this->redirectToRoute(
                        'app_spontaneous_application',
                        ['siret' => $siret]
                    );
                }

                $letterFilename = $defaultLetter;

                $attachments[] = [
                    'path' => $fileUploader->getPath(
                        $defaultLetter
                    ),

                    // Nom visible par le destinataire
                    'name' => 'lettre-de-motivation.pdf',
                ];
            }

            /**
             * =========================
             * ENVOI DU MAIL
             * =========================
             *
             * From:
             * $user->getSenderEmail()
             *
             * Reply-To:
             * géré par EmailService
             */
            $emailService->send(
                user: $user,
                to: $email,
                subject: 'Candidature spontanée',
                template: 'email',
                context: [
                    'message' => $message,
                    'company' => $company,
                    'contact' => $contact,
                    'user' => $user,
                ],
                attachments: $attachments
            );

            /**
             * =========================
             * STOCKAGE DE LA CANDIDATURE
             * =========================
             */
            $application
                ->setProfils($profil)
                ->setCompanies($company)
                ->setStatus(true);

            /**
             * CV utilisé
             */
            $application->setCvUsed($cvFilename);

            /**
             * Lettre utilisée
             */
            $application->setLetterUsed($letterFilename);

            $entityManager->persist($application);
            $entityManager->flush();

            /**
             * Message de succès
             */
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

    #[Route('/mes-candidatures', name: 'app_spontaneous_application_send_list')]
    public function applicationSend(ApplicationsRepository $applicationsRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $profil = $user->getProfils();

        $applications = [];

        if ($profil) {
            $applications = $applicationsRepository->findBy(
                ['profils' => $profil],
                ['sentAt' => 'DESC'] );
        }

        return $this->render('spontaneous_application/application_send_list.html.twig',[
            'applications' => $applications,
        ]);
    }


    #[Route('/messages', name: 'app_spontaneous_message')]
    public function message(
        ImapService $imapService,
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER', 'ROLE_FREELANCE');
        /**
         * @var Users $user;
         */
        $user = $this->getUser();

        $messages = $imapService->getMessagesForView($user);

        return $this->render('spontaneous_application/message.html.twig',
        [
            'messages' => $messages,
        ]);
    }
}


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

    #[Route('/spontaneous/application/send/{siret}', name: 'app_spontaneous_application_send')]
    public function send(
        string $siret,
        Request $request,
        InseeApiService $inseeApiService,
        CompaniesRepository $companiesRepository,
        EmailService $emailService,
        FileUploader $fileUploader,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /**
         * Retrieve company
         */
        $company = $companiesRepository->findOneBy(['siret' => $siret]);

        if (!$company) {
            throw $this->createNotFoundException('Entreprise introuvable.');
        }

        $contacts = $company->getCompanyContacts();

        $emails = [];

        foreach ($contacts as $contact) {
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

            // Fichiers sélectionnés depuis le profil
            $defaultCv = $form->get('defaultCv')->getData();
            $defaultLetter = $form->get('defaultLetter')->getData();

            // Nouveaux fichiers uploadés
            $uploadedCv = $form->get('cv')->getData();
            $uploadedLetter = $form->get('lettreMotivation')->getData();

            $attachments = [];

            /**
             * CV
             */
            if ($uploadedCv) {
                $cvFilename = $fileUploader->upload($uploadedCv);

                $attachments[] = [
                    'path' => $fileUploader->getPath($cvFilename),
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

                $attachments[] = [
                    'path' => $fileUploader->getPath($defaultCv),
                    'name' => 'curriculum-vitae.pdf',
                ];
            }

            /**
             * Lettre de motivation
             */
            if ($uploadedLetter) {
                $letterFilename = $fileUploader->upload($uploadedLetter);

                $attachments[] = [
                    'path' => $fileUploader->getPath($letterFilename),
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

                $attachments[] = [
                    'path' => $fileUploader->getPath($defaultLetter),
                    'name' => 'lettre-de-motivation.pdf',
                ];
            }

            /**
             * Envoi du mail
             * Le EmailService utilise :
             * From:
             *  $user->getSenderEmail();
             * Reply to :
             *  $user->getEmail();
             *
             * @var Users $User
             */
            $user = $this->getUser();

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
             * Stockage de la candidature
             */
            $application
                ->setProfils($profil)
                ->setCompanies($company)
                ->setStatus(true);

            if ($uploadedCv) {
                $application->setCvUsed($cvFilename);
            } else {
                $application->setCvUsed($defaultCv);
            }

            if ($uploadedLetter) {
                $application->setLetterUsed($letterFilename);
            } else {
                $application->setLetterUsed($defaultLetter);
            }

            $entityManager->persist($application);
            $entityManager->flush();

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


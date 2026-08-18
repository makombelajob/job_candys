<?php

namespace App\Controller;

use App\Form\FreelancePropositionType;
use App\Entity\FreelancePropositions;
use App\Form\SearchCompanyType;
use App\Repository\CompaniesRepository;
use App\Repository\CompanyContactsRepository;
use App\Service\InseeApiService;
use App\Service\WappalyzerService;
use App\Service\EmailService;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\FreelancePropositionsRepository;

final class FreelanceApplicationController extends AbstractController
{
    #[Route('/freelance/application', name: 'app_freelance_application')]
    public function index(
        Request $request,
        InseeApiService $inseeApiService,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_FREELANCE');

        /** @var Users $user */
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
            throw $this->createNotFoundException(
                'SIRET manquant.'
            );
        }

        $company = $companiesRepository->findOneBy([
            'siret' => $siret,
        ]);

        if (!$company) {
            throw $this->createNotFoundException(
                'Entreprise introuvable.'
            );
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
        FreelancePropositionsRepository $freelancePropositionsRepository,
        EmailService $emailService,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_FREELANCE');

        /**
         * =========================
         * UTILISATEUR CONNECTÉ
         * =========================
         *
         * @var Users $user
         */
        $user = $this->getUser();

        /**
         * =========================
         * PROFIL
         * =========================
         */
        $profil = $user->getProfils();

        if (!$profil) {
            $this->addFlash(
                'error',
                'Votre profil est introuvable.'
            );

            return $this->redirectToRoute(
                'app_freelance_application'
            );
        }

        /**
         * =========================
         * RECHERCHE DE L'ENTREPRISE
         * =========================
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
         * =========================
         * VÉRIFICATION DOUBLON
         * =========================
         *
         * Un même profil ne peut pas
         * envoyer plusieurs propositions
         * à la même entreprise.
         */
        if (
            $freelancePropositionsRepository
                ->hasProposalForProfileAndCompany(
                    $profil,
                    $company
                )
        ) {
            $this->addFlash(
                'error',
                'Vous avez déjà envoyé une proposition à cette entreprise.'
            );

            return $this->redirectToRoute(
                'app_freelance_application'
            );
        }

        /**
         * =========================
         * RÉCUPÉRATION DES CONTACTS
         * =========================
         */
        $contacts = $companyContactsRepository->findBy([
            'company' => $company,
        ]);

        /**
         * =========================
         * PRÉPARATION DES CHOIX
         * =========================
         */
        $contactChoices = [];

        foreach ($contacts as $contact) {

            $email = trim(
                (string) $contact->getEmail()
            );

            if ($email !== '') {
                $contactChoices[$email] = $email;
            }
        }

        /**
         * =========================
         * CRÉATION DU FORMULAIRE
         * =========================
         */
        $freelanceForm = $this->createForm(
            FreelancePropositionType::class,
            [
                'siret' => $company->getSiret(),
            ],
            [
                'contact_choices' => $contactChoices,
            ]
        );

        $freelanceForm->handleRequest($request);

        /**
         * =========================
         * TRAITEMENT DU FORMULAIRE
         * =========================
         */
        if (
            $freelanceForm->isSubmitted()
            && $freelanceForm->isValid()
        ) {

            $data = $freelanceForm->getData();

            $email = trim(
                (string) $data['email']
            );

            $subject = trim(
                (string) $data['subject']
            );

            $message = (string) $data['message'];

            /**
             * =========================
             * VÉRIFICATION EMAIL
             * =========================
             */
            if ($email === '') {
                $this->addFlash(
                    'error',
                    'L’adresse email du destinataire est obligatoire.'
                );

                return $this->redirectToRoute(
                    'app_freelance_proposition',
                    [
                        'siret' => $siret,
                    ]
                );
            }

            /**
             * =========================
             * NOUVELLE VÉRIFICATION
             * =========================
             *
             * On vérifie à nouveau juste
             * avant l'envoi afin d'éviter
             * un doublon si deux requêtes
             * arrivent presque simultanément.
             */
            if (
                $freelancePropositionsRepository
                    ->hasProposalForProfileAndCompany(
                        $profil,
                        $company
                    )
            ) {
                $this->addFlash(
                    'error',
                    'Vous avez déjà envoyé une proposition à cette entreprise.'
                );

                return $this->redirectToRoute(
                    'app_freelance_application'
                );
            }

            /**
             * =========================
             * ENVOI DU MAIL
             * =========================
             */
            $messageId = $emailService->send(
                user: $user,
                to: $email,
                subject: $subject,
                template: 'freelance_application/email.html.twig',
                context: [
                    'message' => $message,
                    'company' => $company,
                    'siret' => $siret,
                    'subject' => $subject,
                    'recipientEmail' => $email,
                ]
            );

            /**
             * =========================
             * STOCKAGE
             * =========================
             */
            $proposition = new FreelancePropositions();

            $proposition
                ->setProfils($profil)
                ->setCompanies($company)
                ->setRecipientEmail($email)
                ->setSubject($subject)
                ->setMessage($message)
                ->setMessageId($messageId)
                ->setStatus(true)
                ->setSentAt(new \DateTimeImmutable());

            $entityManager->persist($proposition);
            $entityManager->flush();

            /**
             * =========================
             * SUCCÈS
             * =========================
             */
            $this->addFlash(
                'success',
                'Votre proposition a bien été envoyée.'
            );

            return $this->redirectToRoute(
                'app_freelance_application'
            );
        }

        /**
         * =========================
         * AFFICHAGE DU FORMULAIRE
         * =========================
         */
        return $this->render(
            'freelance_application/proposition.html.twig',
            [
                'freelanceForm' => $freelanceForm->createView(),
                'company' => $company,
                'siret' => $company->getSiret(),
            ]
        );
    }
}

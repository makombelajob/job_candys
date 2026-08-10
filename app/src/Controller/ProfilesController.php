<?php

namespace App\Controller;

use App\Entity\Profils;
use App\Form\ProfileEditorType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfilesController extends AbstractController
{
    #[Route('/profiles', name: 'app_profiles')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('profiles/index.html.twig', [
            'controller_name' => 'ProfilesController',
        ]);
    }

    #[Route('/profiles/modify', name: 'app_profiles_modify', methods: ['GET', 'POST'])]
    public function modifyProfiles(
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $profil = $user->getProfils();
        $isNew = $profil === null;

        if ($isNew) {
            $profil = new Profils();
            $user->setProfils($profil);
        }

        /*
         * Un freelance n'a pas besoin de CV ni de lettre de motivation.
         */
        $isFreelance = $this->isGranted('ROLE_FREELANCE');

        $form = $this->createForm(
            ProfileEditorType::class,
            $profil,
            [
                'is_freelance' => $isFreelance,
            ]
        );

        $form->get('firstName')->setData($user->getFirstName());
        $form->get('lastName')->setData($user->getLastName());
        $form->get('email')->setData($user->getEmail());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /*
             * Les CV et lettre ne concernent que les utilisateurs
             * qui ne sont pas freelances.
             */
            if (!$isFreelance) {

                /** @var UploadedFile|null $cv */
                $cv = $form->get('defaultCv')->getData();

                /** @var UploadedFile|null $letter */
                $letter = $form->get('defaultLetter')->getData();

                /*
                 * Pour une nouvelle création de profil classique,
                 * le CV et la lettre sont obligatoires.
                 */
                if ($isNew && (!$cv || !$letter)) {
                    $this->addFlash(
                        'error',
                        'Le CV et la lettre de motivation sont obligatoires.'
                    );

                    return $this->render('profiles/modify.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user,
                        'profil' => $profil,
                    ]);
                }

                if ($cv) {
                    $profil->setDefaultCv(
                        $fileUploader->upload($cv)
                    );
                }

                if ($letter) {
                    $profil->setDefaultLetter(
                        $fileUploader->upload($letter)
                    );
                }
            }

            /*
             * Informations communes à tous les utilisateurs.
             */
            $user->setFirstName(
                $form->get('firstName')->getData()
            );

            $user->setLastName(
                $form->get('lastName')->getData()
            );

            $user->setEmail(
                $form->get('email')->getData()
            );

            $now = new \DateTimeImmutable();

            $user->setUpdatedAt($now);
            $profil->setUpdatedAt($now);

            $entityManager->persist($user);
            $entityManager->persist($profil);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Profil enregistré.'
            );

            return $this->redirectToRoute('app_profiles');
        }

        return $this->render('profiles/modify.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'profil' => $profil,
        ]);
    }
}

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

        /**
         * All rest of code here and hope get it and again
         */
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

        $form = $this->createForm(ProfileEditorType::class, $profil);

        $form->get('firstName')->setData($user->getFirstName());
        $form->get('lastName')->setData($user->getLastName());
        $form->get('email')->setData($user->getEmail());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $cv */
            $cv = $form->get('defaultCv')->getData();

            /** @var UploadedFile|null $letter */
            $letter = $form->get('defaultLetter')->getData();

            if ($isNew && (!$cv || !$letter)) {
                $this->addFlash('error', 'Le CV et la lettre de motivation sont obligatoires.');
            } else {
                if ($cv) {
                    $profil->setDefaultCv($fileUploader->upload($cv));
                }

                if ($letter) {
                    $profil->setDefaultLetter($fileUploader->upload($letter));
                }

                $user->setFirstName($form->get('firstName')->getData());
                $user->setLastName($form->get('lastName')->getData());
                $user->setEmail($form->get('email')->getData());

                $now = new \DateTimeImmutable();
                $user->setUpdatedAt($now);
                $profil->setUpdatedAt($now);

                $entityManager->persist($user);
                $entityManager->persist($profil);
                $entityManager->flush();

                $this->addFlash('success', 'Profil enregistré.');

                return $this->redirectToRoute('app_profiles');
            }
        }

        return $this->render('profiles/modify.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'profil' => $profil,
        ]);
    }
}

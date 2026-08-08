<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Profils;
use App\Form\ProfileEditorType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;



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

    #[Route('/profiles/modify', name: 'app_profiles_modify')]
    public function modifyProfiles(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Utilisateur actuellement connecté
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // Profil associé à l'utilisateur
        $profil = $user->getProfils();

        // Si aucun profil n'existe encore
        if (!$profil) {
            $profil = new Profils();
            $user->setProfils($profil);
        }

        // Création du formulaire basé sur Profils
        $form = $this->createForm(ProfileEditorType::class, $profil);

        // Préremplissage des champs venant de Users
        $form->get('firstName')->setData($user->getFirstName());
        $form->get('lastName')->setData($user->getLastName());
        $form->get('email')->setData($user->getEmail());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Récupération des données Users
            $user->setFirstName($form->get('firstName')->getData());
            $user->setLastName($form->get('lastName')->getData());
            $user->setEmail($form->get('email')->getData());

            // Mise à jour de la date
            $user->setUpdatedAt(new \DateTimeImmutable());
            $profil->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->persist($profil);

            $entityManager->flush();

            return $this->redirectToRoute('app_profiles_modify');
        }

        return $this->render('profiles/modify.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'profil' => $profil,
        ]);
    }
}

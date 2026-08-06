<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\HunterEmailVerify;
use Symfony\Component\Form\FormError;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        HunterEmailVerify $hunterEmailVerify
        ): Response
    {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$hunterEmailVerify->verify($user->getEmail())) {
                $form->get('email')->addError(
                    new FormError('Cette adresse e-mail n\'est pas valide...')
                );
            }

            if ($form->isValid()) {

                /** @var string $plainPassword */
                $plainPassword = $form->get('plainPassword')->getData();

                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );

                $accountType = $form->get('accountType')->getData();

                if ($accountType === 'freelance') {
                    $user->setRoles(['ROLE_FREELANCE']);
                } else {
                    $user->setRoles([]);
                }

                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('app_profiles');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}

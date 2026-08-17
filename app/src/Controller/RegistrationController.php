<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\HunterEmailVerify;
use Symfony\Component\Form\FormError;
use App\Service\UserCreatorService;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        HunterEmailVerify $hunterEmailVerify,
        UserCreatorService $userCreator,
        EmailService $emailService,
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

                /**
                 * @var Users $user;
                 */
                $user->setIsVerified(true);
                
                /** @var string $plainPassword */
                $plainPassword = $form->get('plainPassword')->getData();

                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );

                $accountType = $form->get('accountType')->getData();

                if ($accountType === 'freelance') {
                    $user->setRoles(['ROLE_FREELANCE']);
                } else {
                    $user->setRoles(['ROLE_USER']);
                }

                $userCreator->create($user);

                $emailService->sendRegistrationConfirmation($user);

                return $this->render('registration/confirmation.html.twig', [
                    'user' => $user,
                ]);
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}

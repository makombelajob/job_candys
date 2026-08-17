<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Doctrine\ORM\EntityManagerInterface;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');

        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $email
        );

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials(
                $request->getPayload()->getString('password')
            ),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    $request->getPayload()->getString('_csrf_token')
                ),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {

        /**
         * @var Users $user;
         */
        $user = $token->getUser();

        $user->setLastLogin(new \DateTimeImmutable());
        $this->entityManager->flush();

        /**
         * ADMIN
         */
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_admin')
            );
        }

        /**
         * USERS
         */
        if (in_array('ROLE_USER', $user->getRoles(), true)) {

            // Première connexion :
            // aucun profil n'est encore associé à l'utilisateur.
            if ($user->getProfils() === null) {
                return new RedirectResponse(
                    $this->urlGenerator->generate('app_profiles_modify')
                );
            }

            // Profil déjà créé :
            // on peut éventuellement respecter la TargetPath.
            if ($targetPath = $this->getTargetPath(
                $request->getSession(),
                $firewallName
            )) {
                return new RedirectResponse($targetPath);
            }

            // Connexions suivantes
            return new RedirectResponse(
                $this->urlGenerator->generate('app_profiles')
            );
        }

        /**
         * DEFAULT
         */
        return new RedirectResponse(
            $this->urlGenerator->generate('app_main')
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}


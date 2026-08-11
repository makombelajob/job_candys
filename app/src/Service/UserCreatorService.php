<?php

namespace App\Service;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;

class UserCreatorService
{
    private const SENDER_DOMAIN = 'send.job-candys.jobmakombela.fr';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function create(Users $user): Users
    {
        $user->setSenderEmail(
            $this->generateSenderEmail()
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function generateSenderEmail(): string
    {
        $identifier = bin2hex(random_bytes(8));

        return sprintf(
            'u-%s@%s',
            $identifier,
            self::SENDER_DOMAIN
        );
    }
}
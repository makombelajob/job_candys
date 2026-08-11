<?php

namespace App\Service;

use App\Entity\Users;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    /**
     * Envoie un email au nom de l'utilisateur.
     *
     * L'adresse d'envoi est l'adresse interne Job-Candys
     * de l'utilisateur.
     *
     * Les réponses sont redirigées vers son adresse personnelle.
     *
     * @param array<int, array{path: string, name?: string}> $attachments
     *
     * @throws TransportExceptionInterface
     */
    public function send(
        Users $user,
        string $to,
        string $subject,
        string $template,
        array $context = [],
        array $attachments = []
    ): void {
        $senderEmail = $user->getSenderEmail();
        $personalEmail = $user->getEmail();

        if (!$senderEmail) {
            throw new \RuntimeException(
                'L’utilisateur ne possède pas d’adresse email d’envoi Job-Candys.'
            );
        }

        if (!$personalEmail) {
            throw new \RuntimeException(
                'L’utilisateur ne possède pas d’adresse email personnelle.'
            );
        }

        $senderName = trim(
            sprintf(
                '%s %s',
                $user->getFirstName() ?? '',
                $user->getLastName() ?? ''
            )
        );

        $email = (new TemplatedEmail())
            ->from(new Address($senderEmail, $senderName))
            ->replyTo(new Address($personalEmail, $senderName))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate("spontaneous_application/$template.html.twig")
            ->context($context);

        foreach ($attachments as $attachment) {
            $email->attachFromPath(
                $attachment['path'],
                $attachment['name'] ?? null
            );
        }

        $this->mailer->send($email);
    }
}

<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    /**
     * Envoie un email avec éventuellement des pièces jointes.
     *
     * @param array<int, array{path: string, name?: string}> $attachments
     */
    public function send(
        string $from,
        string $to,
        string $subject,
        string $template,
        array $context = [],
        array $attachments = []
    ): void {
        $email = (new TemplatedEmail())
            ->from($from)
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

<?php

namespace App\Service;

use Webklex\PHPIMAP\ClientManager;
use App\Entity\Users;
use App\Repository\ApplicationsRepository;

class ImapService
{
    private ClientManager $clientManager;

    public function __construct(
        private readonly string $imapHost,
        private readonly int $imapPort,
        private readonly string $imapEncryption,
        private readonly string $imapUsername,
        private readonly string $imapPassword,
        private readonly ApplicationsRepository $applicationsRepository,
    ) {
        $this->clientManager = new ClientManager();
    }

    public function connect()
    {
        return $this->clientManager->make([
            'host'          => $this->imapHost,
            'port'          => $this->imapPort,
            'encryption'    => $this->imapEncryption,
            'validate_cert' => true,
            'username'      => $this->imapUsername,
            'password'      => $this->imapPassword,
            'protocol'      => 'imap',
        ]);
    }

    public function getMessages()
    {
        $client = $this->connect();

        $client->connect();

        $folder = $client->getFolder('INBOX');

        return $folder
            ->messages()
            ->all()
            ->get();
    }

    public function getMessagesForView(Users $user): array
    {
        $profil = $user->getProfils();

        if (!$profil) {
            return [];
        }

        $applications = $this->applicationsRepository->findBy([
            'profils' => $profil,
        ]);

        $messageIds = [];

        foreach ($applications as $application) {
            $messageId = $application->getMessageId();

            if ($messageId) {
                $messageIds[] = trim($messageId, '<>');
            }
        }

        if (!$messageIds) {
            return [];
        }

        $messages = $this->getMessages();

        $result = [];

        foreach ($messages as $message) {
            $from = (string) $message->getFrom();

            if (str_contains(strtolower($from), 'cpanel@')) {
                continue;
            }

            $inReplyTo = trim(
                (string) $message->getInReplyTo(),
                '<>'
            );

            if (!$inReplyTo) {
                continue;
            }

            if (!in_array($inReplyTo, $messageIds, true)) {
                continue;
            }

            $result[] = [
                'subject' => mb_decode_mimeheader(
                    (string) $message->getSubject()
                ),
                'from' => $from,
                'date' => $message->getDate()->first(),
                'text' => (string) $message->getTextBody(),
                'html' => (string) $message->getHTMLBody(),
            ];
        }

        return $result;
    }
}

<?php

namespace App\Service;

use Webklex\PHPIMAP\ClientManager;
use App\Entity\Users;

class ImapService
{
    private ClientManager $clientManager;

    public function __construct(
        private readonly string $imapHost,
        private readonly int $imapPort,
        private readonly string $imapEncryption,
        private readonly string $imapUsername,
        private readonly string $imapPassword,
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
        $messages = $this->getMessages();

        $result = [];

        foreach ($messages as $message) {
            $from = (string) $message->getFrom();

            // Ignorer les messages techniques de cPanel
            if (str_contains(strtolower($from), 'cpanel@')) {
                continue;
            }

            /**
             *  Verifier si l'utilisateur a bel bien des messages
             */
            $to = (string) $message->getTo();
            $userEmail = strtolower((string) $user->getEmail());
            if(!str_contains(strtolower($to), $userEmail)){
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

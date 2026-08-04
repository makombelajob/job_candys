<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class HunterEmailVerify
{
    public function __construct(
        private HttpClientInterface $client,
        private string $hunterApiKey
    ) {
    }

    public function verify(string $email): bool
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.hunter.io/v2/email-verifier',
                [
                    'query' => [
                        'email' => $email,
                        'api_key' => $this->hunterApiKey,
                    ],
                ]
            );

            $data = $response->toArray();

            return ($data['data']['status'] ?? null) === 'valid';

        } catch (ExceptionInterface $e) {
            // Log possible ici
            return false;
        }
    }
}
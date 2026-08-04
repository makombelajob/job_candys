<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InseeApiService
{
    public function __construct(
        private HttpClientInterface $client,

        #[Autowire('%env(INSEE_API_KEY)%')]
        private string $apiKey,
    ) {
    }


    public function searchEntreprise(
        string $mot,
        int $nombre = 1000,
        int $debut = 0
    ): array {

        $response = $this->client->request(
            'GET',
            'https://api.insee.fr/api-sirene/3.11/siren',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-INSEE-Api-Key-Integration' => $this->apiKey,
                ],

                'query' => [
                    'q' => 'periode(denominationUniteLegale:"' . $mot . '")',
                    'nombre' => $nombre,
                    'debut' => $debut,
                ],
            ]
        );

        return $response->toArray();
    }
}
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
        int $nombre = 10000,
        int $debut = 0
    ): array {

        $response = $this->client->request(
            'GET',
            'https://api.insee.fr/api-sirene/3.11/siret',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-INSEE-Api-Key-Integration' => $this->apiKey,
                ],
                'query' => [
                    'q' => 'denominationUniteLegale:' . $mot,
                    'nombre' => $nombre,
                    'debut' => $debut,
                ],
            ]
        );

        return $response->toArray();
    }
    public function findBySiren(string $siren): array
    {
        $response = $this->client->request(
            'GET',
            'https://api.insee.fr/api-sirene/3.11/siret',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-INSEE-Api-Key-Integration' => $this->apiKey,
                ],
                'query' => [
                    'q' => 'siren:' . $siren,
                    'nombre' => 1,
                ],
            ]
        );
        return $response->toArray();
    }
}
<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InseeApiService
{
    public function __construct(
        private HttpClientInterface $client,
        private CacheInterface $cache,

        #[Autowire('%env(INSEE_API_KEY)%')]
        private string $apiKey,
    ) {
    }

    public function searchEntreprise(
        string $mot,
        int $nombre = 20,
        int $debut = 0
    ): array {

        $cacheKey = 'insee_search_' . md5($mot . $nombre . $debut);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($mot, $nombre, $debut) {

            // Cache pendant 1 heure
            $item->expiresAfter(3600);

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
        });
    }


    public function findBySiren(string $siren): array
    {
        $cacheKey = 'insee_siren_' . $siren;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($siren) {

            // Cache pendant 24 heures
            $item->expiresAfter(86400);

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
        });
    }
}
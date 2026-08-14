<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InseeApiService
{
    private const BASE_URL = 'https://api.insee.fr/api-sirene/3.11';

    private const SEARCH_CACHE_TTL = 3600;  // 1 heure
    private const SIREN_CACHE_TTL = 86400;  // 24 heures

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheInterface $cache,

        #[Autowire('%env(INSEE_API_KEY)%')]
        private readonly string $apiKey,
    ) {
    }

    /**
     * Recherche des établissements par nom d'entreprise.
     *
     * Exemples :
     *   Digital 113
     *   Maison Dupont
     *   Jean Pierre Martin
     *
     * Les différents mots sont recherchés dans la dénomination.
     *
     * @return array<string, mixed>
     */
    public function searchEntreprise(
        string $mot,
        int $nombre = 100,
        int $debut = 0,
    ): array {
        $mot = $this->normalizeSearchTerm($mot);

        if ($mot === '') {
            return [];
        }

        $nombre = max(1, min($nombre, 1000));
        $debut = max(0, $debut);

        $cacheKey = sprintf(
            'insee_search_%s_%d_%d',
            md5(mb_strtolower($mot)),
            $nombre,
            $debut
        );

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($mot, $nombre, $debut): array {
                $item->expiresAfter(self::SEARCH_CACHE_TTL);

                return $this->request('/siret', [
                    'q' => $this->buildDenominationQuery($mot),
                    'nombre' => $nombre,
                    'debut' => $debut,
                ]);
            }
        );
    }

    /**
     * Recherche les établissements d'une entreprise à partir du SIREN.
     *
     * @return array<string, mixed>
     */
    public function findBySiren(string $siren): array
    {
        $siren = preg_replace('/\D/', '', $siren);

        if ($siren === null || strlen($siren) !== 9) {
            return [];
        }

        $cacheKey = 'insee_siren_' . $siren;

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($siren): array {
                $item->expiresAfter(self::SIREN_CACHE_TTL);

                return $this->request('/siret', [
                    'q' => 'siren:' . $siren,
                    'nombre' => 100,
                    'debut' => 0,
                ]);
            }
        );
    }

    /**
     * Construit la requête Sirene pour un nom composé.
     *
     * Exemple :
     *
     *   "Digital 113"
     *
     * devient :
     *
     *   denominationUniteLegale:(Digital AND 113)
     */
    private function buildDenominationQuery(string $search): string
    {
        $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || $words === []) {
            return '';
        }

        $words = array_map(
            static fn (string $word): string => self::escapeQueryValue($word),
            $words
        );

        if (count($words) === 1) {
            return 'denominationUniteLegale:' . $words[0];
        }

        return 'denominationUniteLegale:(' . implode(' AND ', $words) . ')';
    }

    /**
     * Nettoie la saisie utilisateur.
     */
    private function normalizeSearchTerm(string $value): string
    {
        $value = trim($value);

        // Plusieurs espaces deviennent un seul.
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

    /**
     * Protège les caractères spéciaux utilisés par la syntaxe de recherche.
     */
    private static function escapeQueryValue(string $value): string
    {
        return preg_replace(
            '/([+\-&|!(){}\[\]^"~*?:\\\\\/])/u',
            '\\\\$1',
            $value
        ) ?? $value;
    }

    /**
     * Effectue une requête vers l'API Sirene.
     *
     * L'API retourne 404 lorsqu'aucun résultat n'est trouvé.
     * Dans ce cas, le service retourne simplement [].
     *
     * @param array<string, scalar> $query
     *
     * @return array<string, mixed>
     */
    private function request(string $endpoint, array $query): array
    {
        try {
            $response = $this->client->request(
                'GET',
                self::BASE_URL . $endpoint,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'X-INSEE-Api-Key-Integration' => $this->apiKey,
                    ],
                    'query' => $query,
                    'timeout' => 10,
                ]
            );

            $statusCode = $response->getStatusCode();

            /*
             * Sirene utilise HTTP 404 lorsqu'aucun résultat
             * ne correspond à la requête.
             */
            if ($statusCode === 404) {
                return [];
            }

            $content = $response->getContent(false);

            if ($statusCode >= 400) {
                throw new \RuntimeException(
                    sprintf(
                        'Erreur API INSEE (%d) : %s',
                        $statusCode,
                        $content
                    )
                );
            }

            if ($content === '') {
                return [];
            }

            $data = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!is_array($data)) {
                throw new \RuntimeException(
                    'La réponse de l\'API INSEE est invalide.'
                );
            }

            return $data;
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException(
                'Impossible de contacter l\'API INSEE.',
                0,
                $e
            );
        } catch (\JsonException $e) {
            throw new \RuntimeException(
                'La réponse de l\'API INSEE contient un JSON invalide.',
                0,
                $e
            );
        }
    }
}


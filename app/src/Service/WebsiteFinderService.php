<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class WebsiteFinderService
{
    private const EXTENSIONS = [
        '.fr',
        '.net',
        '.com',
        '.eu',
        '.bzh',
        '.ma',
        '.aero',
    ];

    private const TIMEOUT = 10;
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Recherche le site internet d'une entreprise.
     * 
     * @param string $name Le nom de l'entreprise
     * @return string|null L'URL du site trouvé, ou null
     * @throws \InvalidArgumentException Si le nom est vide ou invalide
     */
    public function findWebsite(string $name): ?string
    {
        $name = trim($name);
        
        if (empty($name)) {
            throw new \InvalidArgumentException('Company name cannot be empty');
        }

        if (mb_strlen($name) > 255) {
            throw new \InvalidArgumentException('Company name is too long (max 255 characters)');
        }

        $this->logger->info("Starting website search for company", ['name' => $name]);
        
        $variants = $this->generateVariants($name);

        foreach ($variants as $variant) {
            foreach (self::EXTENSIONS as $extension) {
                $domain = $variant . $extension;
                
                try {
                    $website = $this->checkDomain($domain);

                    if ($website !== null) {
                        $this->logger->info("Website found", [
                            'company' => $name,
                            'url' => $website,
                        ]);
                        return $website;
                    }
                } catch (\Exception $e) {
                    $this->logger->debug("Error checking domain", [
                        'domain' => $domain,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->logger->info("No website found for company", ['name' => $name]);
        return null;
    }

    /**
     * Vérifie si un domaine est accessible et retourne son contenu.
     * 
     * @param string $domain Le domaine à vérifier (sans https://)
     * @return string|null L'URL complète si accessible, null sinon
     */
    private function checkDomain(string $domain): ?string
    {
        $url = !empty($domain) ? "https://{$domain}" : "https://www.{$domain}";

        try {
            $response = $this->httpClient->request(
                'GET',
                $url,
                [
                    'timeout' => self::TIMEOUT,
                    'max_redirects' => 5,
                    'headers' => [
                        'User-Agent' => self::USER_AGENT,
                    ],
                ]
            );

            // Vérifier le statut HTTP
            if ($response->getStatusCode() >= 400) {
                return null;
            }

            $html = $response->getContent();

            if (empty($html)) {
                return null;
            }

            $this->readHomepage($html, $url);

            return $url;
        } catch (HttpExceptionInterface $e) {
            // HTTP errors are logged but not thrown
            return null;
        } catch (\Exception $e) {
            $this->logger->warning("Failed to check domain: {$domain}", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extrait et analyse les informations principales de la page d'accueil.
     * 
     * @param string $html Le contenu HTML de la page
     * @param string $url L'URL de la page
     */
    private function readHomepage(string $html, string $url): void
    {
        libxml_use_internal_errors(true);

        try {
            $dom = new \DOMDocument();
            $dom->loadHTML($html);

            $this->extractTitle($dom);
            $this->extractDescription($dom);
            $this->extractBodyContent($html);
        } finally {
            libxml_use_internal_errors(false);
        }
    }

    /**
     * Extrait le titre de la page.
     */
    private function extractTitle(\DOMDocument $dom): void
    {
        $titles = $dom->getElementsByTagName('title');

        if ($titles->length > 0) {
            $title = trim($titles->item(0)->textContent);
            if (!empty($title)) {
                $this->logger->debug("Page title found", ['title' => mb_substr($title, 0, 100)]);
            }
        }
    }

    /**
     * Extrait la description meta de la page.
     */
    private function extractDescription(\DOMDocument $dom): void
    {
        $metas = $dom->getElementsByTagName('meta');

        foreach ($metas as $meta) {
            if (strtolower($meta->getAttribute('name')) === 'description') {
                $description = $meta->getAttribute('content');
                if (!empty($description)) {
                    $this->logger->debug("Meta description found", ['description' => mb_substr($description, 0, 100)]);
                }
                break;
            }
        }
    }

    /**
     * Extrait un aperçu du contenu textuel de la page.
     */
    private function extractBodyContent(string $html): void
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        if (strlen($text) > 0) {
            $preview = mb_substr($text, 0, 200);
            $this->logger->debug("Page content preview", ['preview' => $preview]);
        }
    }

    /**
     * Génère les variantes possibles du nom d'entreprise.
     * 
     * Crée plusieurs variantes du nom pour augmenter les chances de trouver le domaine :
     * - Sans espaces
     * - Avec tirets à la place des espaces
     * - Premier mot seul (si au moins 4 caractères)
     * 
     * @param string $name Le nom de l'entreprise
     * @return array<int, string> Les variantes du nom générées
     */
    private function generateVariants(string $name): array
    {
        $clean = strtolower(trim($name));

        // Supprimer les caractères spéciaux
        $clean = preg_replace('/[^a-z0-9\s\-]/', '', $clean);
        $clean = trim($clean);

        if (empty($clean)) {
            return [];
        }

        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY);

        $variants = [
            str_replace(' ', '', $clean),      // "CompanyName"
            str_replace(' ', '-', $clean),     // "company-name"
        ];

        // Ajouter le premier mot s'il est suffisamment long
        if (!empty($words) && mb_strlen($words[0]) >= 4) {
            $variants[] = $words[0];
        }

        // Retirer les doublons et réindexer
        return array_values(array_unique($variants));
    }
}
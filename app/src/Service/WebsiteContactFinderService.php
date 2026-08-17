<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class WebsiteContactFinderService
{
    private const CONTACT_KEYWORDS = [
        'contact',
        'contact-us',
        'contactez',
        'nous-contacter',
        'get-in-touch',
        'localisation',
        'adresse',
        'agence',
    ];

    private const LEGAL_KEYWORDS = [
        'mention',
        'mentions',
        'legal',
        'legal-notice',
        'imprint',
    ];

    private const COMMON_CONTACT_PATHS = [
        '/contact',
        '/contact/',
        '/contact-us',
        '/contactez-nous',
        '/en/contact-us',
        '/en/contact',
        '/en/contacts',
        '/nous-contacter',
        '/fr/contactez-nous',
        '/localisation',
        '/localisation.php',
        '/localisation.php?lang=fr',
        '/mentions-legales',
        '/mentions-legales.php',
        '/legal-notice/',
        '/conditions-generales-dutilisation/',
    ];

    private const EXCLUDED_KEYWORDS = [
        'rgpd',
        'dpo',
        'dpd',
        'privacy',
        'security',
        'vuln',
        'abuse',
        'noreply',
        'no-reply',
        'donotreply',
    ];

    private const TIMEOUT = 10;
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Recherche les contacts d'une entreprise via son site web.
     * 
     * @param string $website L'URL du site web
     * @return array|null Les emails trouvés, ou null
     * @throws \InvalidArgumentException Si l'URL est invalide
     */
    public function findContacts(string $website): ?array
    {
        $website = trim($website);

        if (empty($website)) {
            throw new \InvalidArgumentException('Website URL cannot be empty');
        }

        if (!filter_var($website, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid website URL format');
        }

        $website = rtrim($website, '/');

        $this->logger->info("Starting contact search for website", ['website' => $website]);

        try {
            $homepage = $this->loadPage($website);

            if ($homepage === null) {
                $this->logger->info("Could not load homepage", ['website' => $website]);
                return null;
            }

            $links = $this->extractLinks($homepage, $website);

            $links = array_merge(
                $links,
                $this->generateCommonPages($website)
            );

            // Try contact pages first
            $emails = $this->findEmailsInPages(
                array_unique($links),
                $website,
                true
            );

            if (!empty($emails)) {
                return $emails;
            }

            // Then try legal pages
            return $this->findEmailsInPages(
                array_unique($links),
                $website,
                false
            );
        } catch (\Exception $e) {
            $this->logger->error("Error during contact search", [
                'website' => $website,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function findEmailsInPages(
        array $links,
        string $website,
        bool $contactOnly
    ): ?array {
        foreach ($links as $link) {

            if ($contactOnly && !$this->isContactPage($link)) {
                continue;
            }

            if (!$contactOnly && !$this->isLegalPage($link)) {
                continue;
            }

            $this->logger->debug("Analyzing page", ['page' => $link]);

            try {
                $html = $this->loadPage($link);

                if ($html === null) {
                    continue;
                }

                $emails = $this->extractEmails($html, $website);

                if (!empty($emails)) {
                    $this->logger->info("Contacts found", [
                        'website' => $website,
                        'count' => count($emails),
                    ]);
                    return $emails;
                }
            } catch (\Exception $e) {
                $this->logger->debug("Error analyzing page", [
                    'page' => $link,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return null;
    }

    private function generateCommonPages(string $website): array
    {
        $pages = [];

        foreach (self::COMMON_CONTACT_PATHS as $path) {
            $pages[] = $website . $path;
        }

        return $pages;
    }

    private function loadPage(string $url): ?string
    {
        $this->logger->debug("Loading page", ['url' => $url]);

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

            if ($response->getStatusCode() >= 400) {
                return null;
            }

            $html = $response->getContent();

            if (empty($html)) {
                return null;
            }

            return $html;
        } catch (HttpExceptionInterface $e) {
            return null;
        } catch (\Exception $e) {
            $this->logger->warning("Failed to load page", [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function extractLinks(
        string $html,
        string $baseUrl
    ): array {
        libxml_use_internal_errors(true);

        try {
            $dom = new \DOMDocument();
            $dom->loadHTML($html);

            $links = [];

            foreach ($dom->getElementsByTagName('a') as $node) {

                $href = trim($node->getAttribute('href'));

                if (!$href) {
                    continue;
                }

                if (!str_starts_with($href, 'http')) {

                    if (str_starts_with($href, '/')) {
                        $href = $baseUrl . $href;
                    } else {
                        $href = $baseUrl . '/' . $href;
                    }
                }

                if (str_starts_with($href, 'http')) {
                    $links[] = $href;
                }
            }

            return array_values(array_unique($links));
        } finally {
            libxml_use_internal_errors(false);
        }
    }

    private function isContactPage(string $url): bool
    {
        return $this->containsKeyword($url, self::CONTACT_KEYWORDS);
    }

    private function isLegalPage(string $url): bool
    {
        return $this->containsKeyword($url, self::LEGAL_KEYWORDS);
    }

    private function containsKeyword(string $value, array $keywords): bool
    {
        $path = parse_url($value, PHP_URL_PATH);
        $path = strtolower($path ?? '');

        foreach ($keywords as $keyword) {
            if (str_contains($path, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function extractEmails(string $html, string $website): array
    {
        $domain = parse_url($website, PHP_URL_HOST);
        $domain = preg_replace('/^www\./', '', strtolower($domain));

        preg_match_all(
            '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i',
            strip_tags($html),
            $matches
        );

        $emails = [];

        foreach ($matches[0] as $email) {

            $email = strtolower(trim($email));

            if ($this->isExcluded($email)) {
                continue;
            }

            $emailDomain = substr(strrchr($email, '@'), 1);

            if ($emailDomain !== $domain) {
                continue;
            }

            $emails[] = $email;
        }

        $emails = array_values(array_unique($emails));

        usort(
            $emails,
            function ($a, $b) {
                return $this->emailScore($b) <=> $this->emailScore($a);
            }
        );

        return $emails;
    }

    private function emailScore(string $email): int
    {
        $score = 0;

        if (str_contains($email, 'contact')) {
            $score += 100;
        }

        if (str_contains($email, 'info')) {
            $score += 90;
        }

        if (str_contains($email, 'support')) {
            $score += 80;
        }

        if (str_contains($email, 'question')) {
            $score += 70;
        }

        if (str_contains($email, 'hr')) {
            $score += 40;
        }

        if (str_contains($email, 'recruit')) {
            $score += 30;
        }

        return $score;
    }

    private function isExcluded(string $email): bool
    {
        foreach (self::EXCLUDED_KEYWORDS as $keyword) {
            if (str_contains($email, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
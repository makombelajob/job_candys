<?php

namespace App\Service;

class WebsiteContactFinderService
{
    private const CONTACT_KEYWORDS = [
        'contact',
        'contact-us',
        'contactez',
        'nous-contacter',
        'get-in-touch',
        'localisation'
    ];

    private const LEGAL_KEYWORDS = [
        'mention',
        'mentions',
        'legal',
        'legal-notice',
        'imprint',
    ];

    private const EXCLUDED_KEYWORDS = [
        'rgpd',
        'dpo',
        'privacy',
        'security',
        'vuln',
        'abuse',
        'noreply',
        'no-reply',
        'donotreply',
    ];

    public function findContacts(string $website): ?array
    {
        $website = rtrim($website, '/');

        $homepage = $this->loadPage($website);

        if ($homepage === null) {
            return null;
        }

        $links = $this->extractLinks(
            $homepage,
            $website
        );

        foreach ([
            '/contact',
            '/contact/',
            '/contact-us',
            '/contactez-nous',
            '/nous-contacter',
            '/localisation',
            '/localisation.php',
            '/mentions-legales',
            '/mentions-legales.php',
        ] as $path) {
            $links[] = $website . $path;
        }

        $links = array_values(
            array_unique($links)
        );

        $emails = $this->findEmailsInPages(
            $links,
            $website,
            true
        );

        if (!empty($emails)) {
            return $emails;
        }

        return $this->findEmailsInPages(
            $links,
            $website,
            false
        );
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

            $this->log(
                "Page analysée : {$link}"
            );

            $html = $this->loadPage($link);

            if ($html === null) {
                continue;
            }

            $emails = $this->extractEmails(
                $html,
                $website
            );

            if (!empty($emails)) {
                return $emails;
            }
        }

        return null;
    }

    private function loadPage(string $url): ?string
    {
        $this->log(
            "Lecture : {$url}"
        );

        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);

        $html = curl_exec($curl);

        $code = curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );

        curl_close($curl);

        if (
            $code >= 200 &&
            $code < 400 &&
            !empty($html)
        ) {
            return $html;
        }

        return null;
    }

    private function extractLinks(
        string $html,
        string $baseUrl
    ): array {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();

        $dom->loadHTML($html);

        $links = [];

        foreach ($dom->getElementsByTagName('a') as $node) {

            $href = trim(
                $node->getAttribute('href')
            );

            if (!$href) {
                continue;
            }

            if (str_starts_with($href, '/')) {
                $href = $baseUrl . $href;
            }

            if (str_starts_with($href, 'http')) {
                $links[] = $href;
            }
        }

        return array_values(
            array_unique($links)
        );
    }
        private function isContactPage(string $url): bool
    {
        return $this->containsKeyword(
            $url,
            self::CONTACT_KEYWORDS
        );
    }

    private function isLegalPage(string $url): bool
    {
        return $this->containsKeyword(
            $url,
            self::LEGAL_KEYWORDS
        );
    }

    private function containsKeyword(
        string $value,
        array $keywords
    ): bool {
        $value = strtolower($value);

        foreach ($keywords as $keyword) {

            if (str_contains($value, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function extractEmails(
        string $html,
        string $website
    ): array {
        $domain = parse_url(
            $website,
            PHP_URL_HOST
        );

        $domain = preg_replace(
            '/^www\./',
            '',
            strtolower($domain)
        );

        $emails = [];

        /*
         * Emails présents directement dans le HTML
         */
        preg_match_all(
            '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i',
            strip_tags($html),
            $matches
        );

        foreach ($matches[0] as $email) {

            $email = strtolower(
                trim($email)
            );

            $emailDomain = substr(
                strrchr($email, '@'),
                1
            );

            if (
                $emailDomain === $domain &&
                !$this->isExcluded($email)
            ) {
                $emails[] = $email;
            }
        }

        /*
         * Reconstruction des emails générés en JavaScript
         *
         * Exemple :
         * var info = "info";
         * window.location.href="mailto:" + info + "@" + "uuds.com";
         */

        preg_match_all(
            '/(?:var|let|const)\s+([a-zA-Z0-9_]+)\s*=\s*[\'"]([^\'"]+)[\'"]/i',
            $html,
            $variables,
            PREG_SET_ORDER
        );

        $vars = [];

        foreach ($variables as $variable) {
            $vars[$variable[1]] = $variable[2];
        }

        preg_match_all(
            '/mailto:\s*"\s*\+\s*([a-zA-Z0-9_]+)\s*\+\s*"@"\s*\+\s*"([^"]+)"/i',
            $html,
            $mailtos,
            PREG_SET_ORDER
        );

        foreach ($mailtos as $mailto) {

            $name = $mailto[1];
            $host = strtolower($mailto[2]);

            if (!isset($vars[$name])) {
                continue;
            }

            $email = strtolower(
                $vars[$name] . '@' . $host
            );

            $emailDomain = preg_replace(
                '/^www\./',
                '',
                $host
            );

            if (
                $emailDomain === $domain &&
                !$this->isExcluded($email)
            ) {
                $emails[] = $email;
            }
        }

        /*
         * Deuxième forme :
         *
         * "mailto:" + info + "@" + "uuds.com"
         */

        preg_match_all(
            '/"mailto:"\s*\+\s*([a-zA-Z0-9_]+)\s*\+\s*"@"\s*\+\s*"([^"]+)"/i',
            $html,
            $mailtos,
            PREG_SET_ORDER
        );

        foreach ($mailtos as $mailto) {

            $name = $mailto[1];
            $host = strtolower($mailto[2]);

            if (!isset($vars[$name])) {
                continue;
            }

            $email = strtolower(
                $vars[$name] . '@' . $host
            );

            $emailDomain = preg_replace(
                '/^www\./',
                '',
                $host
            );

            if (
                $emailDomain === $domain &&
                !$this->isExcluded($email)
            ) {
                $emails[] = $email;
            }
        }

        $emails = array_values(
            array_unique($emails)
        );

        usort(
            $emails,
            fn(string $a, string $b)
                => $this->emailScore($b)
                <=> $this->emailScore($a)
        );

        return $emails;
    }
}
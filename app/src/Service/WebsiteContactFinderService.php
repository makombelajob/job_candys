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

        $links = $this->extractLinks($homepage, $website);

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

            $this->log("Page analysée : {$link}");

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
        $this->log("Lecture : {$url}");

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

        if ($code >= 200 && $code < 400 && !empty($html)) {
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

        preg_match_all(
            '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i',
            strip_tags($html),
            $matches
        );

        $emails = [];

        foreach ($matches[0] as $email) {

            $email = strtolower(
                trim($email)
            );

            if ($this->isExcluded($email)) {
                continue;
            }

            $emailDomain = substr(
                strrchr($email, '@'),
                1
            );

            if ($emailDomain !== $domain) {
                continue;
            }

            $emails[] = $email;
        }

        $emails = array_values(
            array_unique($emails)
        );

        usort(
            $emails,
            function ($a, $b) {
                return $this->emailScore($b)
                    <=> $this->emailScore($a);
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
            $score += 20;
        }

        if (str_contains($email, 'recruit')) {
            $score += 10;
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

    private function log(string $message): void
    {
        file_put_contents(
            'php://stderr',
            $message . PHP_EOL
        );
    }
}
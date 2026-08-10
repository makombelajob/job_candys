<?php

namespace App\Service;

class WebsiteFinderService
{
    private const EXTENSIONS = [
        '.fr',
        '.net',
        '.com',
        '.eu',
        '.bzh',
    ];

    /**
     * Recherche le site internet d'une entreprise.
     */
    public function findWebsite(string $name): ?string
    {
        $variants = $this->generateVariants($name);

        foreach ($variants as $variant) {

            foreach (self::EXTENSIONS as $extension) {

                $domain = $variant . $extension;

                $this->log("Test : {$domain}");

                $website = $this->checkDomain($domain);

                if ($website !== null) {
                    return $website;
                }
            }
        }

        return null;
    }

    /**
     * Vérifie si un domaine est accessible.
     */
    private function checkDomain(string $domain): ?string
    {
        $url = "https://{$domain}";

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);

        $html = curl_exec($ch);

        $httpCode = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        curl_close($ch);

        if ($httpCode > 0 && $httpCode < 400 && !empty($html)) {

            $this->readHomepage($html, $url);

            return $url;
        }

        return null;
    }

    /**
     * Lecture simple de la page d'accueil.
     */
    private function readHomepage(string $html, string $url): void
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();

        $dom->loadHTML($html);

        // Titre
        $titles = $dom->getElementsByTagName('title');

        if ($titles->length > 0) {

            $title = trim(
                $titles->item(0)->textContent
            );

            $this->log(
                "Titre : {$title}"
            );
        }

        // Description
        $metas = $dom->getElementsByTagName('meta');

        foreach ($metas as $meta) {

            if (
                strtolower($meta->getAttribute('name')) === 'description'
            ) {

                $description = $meta->getAttribute('content');

                $this->log(
                    "Description : {$description}"
                );
            }
        }

        // Texte visible
        $text = strip_tags($html);

        $text = preg_replace(
            '/\s+/',
            ' ',
            $text
        );

        $this->log(
            "Début contenu : " .
            mb_substr($text, 0, 300)
        );
    }

    /**
     * Génère les variantes possibles du nom.
     */
    private function generateVariants(string $name): array
    {
        $clean = strtolower(trim($name));

        $clean = str_replace(
            "'",
            '',
            $clean
        );

        $words = preg_split(
            '/\s+/',
            $clean
        );

        $variants = [
            str_replace(' ', '', $clean),
            str_replace(' ', '-', $clean),
        ];

        if (
            !empty($words)
            &&
            mb_strlen($words[0]) >= 4
        ) {

            $variants[] = $words[0];
        }

        return array_values(
            array_unique($variants)
        );
    }


    /**
     * Log temporaire.
     */
    private function log(string $message): void
    {
        file_put_contents(
            'php://stderr',
            $message . PHP_EOL
        );
    }
}
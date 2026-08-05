<?php

namespace App\Service;

class WebsiteFinderService
{
    private const EXTENSIONS = [
        '.com',
        '.fr',
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
        $ch = curl_init("https://{$domain}");

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_NOBODY => true,
        ]);

        curl_exec($ch);

        $httpCode = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        curl_close($ch);


        if ($httpCode > 0 && $httpCode < 400) {
            return "https://{$domain}";
        }

        return null;
    }


    /**
     * Génère les variantes possibles du nom.
     */
    private function generateVariants(string $name): array
    {
        $clean = strtolower(trim($name));
        $clean = str_replace("'", '', $clean);

        $words = preg_split('/\s+/', $clean);


        $variants = [
            str_replace(' ', '', $clean),
            str_replace(' ', '-', $clean),
        ];


        if (!empty($words) && mb_strlen($words[0]) >= 4) {
            $variants[] = $words[0];
        }


        return array_values(
            array_unique($variants)
        );
    }


    /**
     * Affichage temporaire des recherches.
     */
    private function log(string $message): void
    {
        file_put_contents(
            'php://stderr',
            $message . PHP_EOL
        );
    }
}
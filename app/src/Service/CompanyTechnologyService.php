<?php

namespace App\Service;

class CompanyTechnologyService
{
    public function __construct(
        private WebsiteFinderService $websiteFinder,
        private WappalyzerService $wappalyzer,
    ) {
    }

    public function analyzeCompany(string $companyName): array
    {
        // On utilise ton service existant, sans le modifier
        $website = $this->websiteFinder->findWebsite($companyName);

        // Aucun site trouvé
        if ($website === null) {
            return [
                'website' => null,
                'technologies' => [],
            ];
        }

        // Nouveau traitement avec Wappalyzer
        $technologies = $this->wappalyzer->analyze($website);

        return [
            'website' => $website,
            'technologies' => $technologies,
        ];
    }
}
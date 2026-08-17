<?php

namespace App\Service;

use MadeITBelgium\Wappalyzer\Wappalyzer;
use Psr\Log\LoggerInterface;

class WappalyzerService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function analyze(?string $website): array
    {
        if (empty($website)) {
            return [];
        }

        try {
            $wappalyzer = new Wappalyzer();

            return $wappalyzer->analyze($website);
        } catch (\Throwable $e) {
            $this->logger->error('Wappalyzer analysis failed', [
                'website' => $website,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
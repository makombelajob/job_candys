<?php

namespace App\Service;

use App\Repository\VisitorsRepository;

class IpVisitLimitService
{
    private const MAX_VISITS = 100;

    public function __construct(
        private VisitorsRepository $visitorRepository,
    ) {
    }

    public function isBlocked(?string $ip): bool
    {
        if ($ip === null) {
            return false;
        }

        return $this->visitorRepository->hasReachedVisitLimit(
            $ip,
            self::MAX_VISITS
        );
    }
}
<?php

namespace App\Service;

use App\Entity\Visitors;
use App\Repository\VisitorsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class VisitorTrackingService
{
    private const SESSION_KEY = 'visitor_tracking_visit';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private VisitorsRepository $visitorRepository,
    ) {
    }

    public function track(Request $request): void
    {
        /*
         * Si cette session a déjà été comptabilisée,
         * on ne fait rien.
         */
        if ($request->getSession()->has(self::SESSION_KEY)) {
            return;
        }

        $ip = $request->getClientIp();

        if (!$ip) {
            return;
        }

        $userAgent = $request->headers->get('User-Agent') ?? 'Unknown';
        $now = new \DateTimeImmutable();

        $visitor = $this->visitorRepository->findOneBy([
            'ipAddress' => $ip,
        ]);

        /*
         * Premier visiteur connu pour cette IP.
         */
        if (!$visitor) {
            $visitor = new Visitors();

            $visitor->setIpAddress($ip);
            $visitor->setUserAgent($userAgent);
            $visitor->setVisitCount(1);
            $visitor->setFirstVisitAt($now);
            $visitor->setLastVisitAt($now);

            $this->entityManager->persist($visitor);
        } else {
            /*
             * Nouvelle visite pour un visiteur existant.
             */
            $visitor->setVisitCount(
                $visitor->getVisitCount() + 1
            );

            $visitor->setUserAgent($userAgent);
            $visitor->setLastVisitAt($now);
        }

        /*
         * On mémorise que cette session a déjà été comptabilisée.
         */
        $request->getSession()->set(
            self::SESSION_KEY,
            true
        );

        $this->entityManager->flush();
    }
}
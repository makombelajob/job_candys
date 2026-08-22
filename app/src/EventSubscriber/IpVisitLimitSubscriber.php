<?php

namespace App\EventSubscriber;

use App\Service\IpVisitLimitService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class IpVisitLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IpVisitLimitService $ipVisitLimitService,
    ) {
    }

    public function onRequestEvent(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $ip = $request->getClientIp();

        if ($this->ipVisitLimitService->isBlocked($ip)) {
            $event->setResponse(
                new Response(
                    'Access denied.',
                    Response::HTTP_FORBIDDEN
                )
            );

            return;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                'onRequestEvent',
                100,
            ],
        ];
    }
}
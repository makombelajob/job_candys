<?php

namespace App\EventSubscriber;

use App\Service\VisitorTrackingService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class VisitorTrackingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VisitorTrackingService $visitorTrackingService,
    ) {
    }

    public function onRequestEvent(RequestEvent $event): void
    {
        /*
         * On ne traite que la requête principale.
         */
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        /*
         * On laisse le service gérer le tracking.
         */
        $this->visitorTrackingService->track($request);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequestEvent',
        ];
    }
}
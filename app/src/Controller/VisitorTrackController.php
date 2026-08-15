<?php

namespace App\Controller;

use App\Service\VisitorTrackingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\VisitorsRepository;

final class VisitorTrackController extends AbstractController
{
    #[Route('/visitor/track', name: 'app_visitor_track')]
    public function index(
        VisitorTrackingService $visitorTrackingService,
        VisitorsRepository $visitorsRepository,
    ): Response {
        
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $visitors = $visitorsRepository->findAllVisitors();

        return $this->render('visitor_track/index.html.twig', [
            'visitors' => $visitors,
        ]);
    }
}
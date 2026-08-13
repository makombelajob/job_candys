<?php

namespace App\Controller;

use App\Service\WebsiteFinderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestWebsiteFinderController extends AbstractController
{
    #[Route('/test/website-finder/{company}', name: 'app_test_website_finder')]
    public function testWebsiteFinder(string $company, WebsiteFinderService $websiteFinderService): Response
    {
        $website = $websiteFinderService->findWebsite($company);

        return $this->render('test_website_finder.html.twig', [
            'company' => $company,
            'website' => $website,
        ]);
    }
}

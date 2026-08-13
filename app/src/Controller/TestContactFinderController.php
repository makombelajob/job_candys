<?php

namespace App\Controller;

use App\Service\WebsiteContactFinderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestContactFinderController extends AbstractController
{
    #[Route('/test/contact-finder', name: 'app_test_contact_finder')]
    public function testContactFinder(Request $request, WebsiteContactFinderService $contactFinderService): Response
    {
        $website = $request->query->get('website', '');
        $emails = null;
        $errorMessage = null;

        if (!empty($website)) {
            try {
                $emails = $contactFinderService->findContacts($website);
            } catch (\InvalidArgumentException $e) {
                $errorMessage = 'Erreur de validation : ' . $e->getMessage();
            } catch (\Exception $e) {
                $errorMessage = 'Erreur lors de la recherche de contacts : ' . $e->getMessage();
            }
        }

        return $this->render('test_contact_finder.html.twig', [
            'website' => $website,
            'emails' => $emails,
            'errorMessage' => $errorMessage,
        ]);
    }
}

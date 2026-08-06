<?php

namespace App\Controller;

use App\Repository\CompaniesRepository;
use App\Service\WebsiteContactFinderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FindContactController extends AbstractController
{
    public function __construct(
        private CompaniesRepository $companiesRepository,
        private WebsiteContactFinderService $websiteContactFinderService
    ) {
    }

    #[Route('/find/contact/{siret}', name: 'app_find_contact')]
    public function index(string $siret): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $company = $this->companiesRepository
            ->findOneBy([
                'siret' => $siret
            ]);

        if (!$company) {
            return $this->render(
                'find_contact/index.html.twig',
                [
                    'emails' => null,
                    'message' => 'Entreprise introuvable.',
                ]
            );
        }

        $website = $company->getWebSite();

        if (!$website) {
            return $this->render(
                'find_contact/index.html.twig',
                [
                    'emails' => null,
                    'message' => 'Aucun site enregistré.',
                ]
            );
        }

        $emails = $this->websiteContactFinderService
            ->findContacts($website);

        return $this->render(
            'find_contact/index.html.twig',
            [
                'company' => $company,
                'website' => $website,
                'emails' => $emails,
            ]
        );
    }
}
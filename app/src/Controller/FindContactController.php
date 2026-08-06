<?php

namespace App\Controller;

use App\Entity\CompanyContacts;
use App\Repository\CompaniesRepository;
use App\Service\WebsiteContactFinderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FindContactController extends AbstractController
{
    public function __construct(
        private CompaniesRepository $companiesRepository,
        private WebsiteContactFinderService $websiteContactFinderService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/find/contact/{siret}', name: 'app_find_contact')]
    public function index(string $siret): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $company = $this->companiesRepository->findOneBy([
            'siret' => $siret
        ]);

        if (!$company) {
            return $this->render('find_contact/index.html.twig', [
                'emails' => null,
                'message' => 'Entreprise introuvable.',
            ]);
        }

        $website = $company->getWebSite();

        if (!$website) {
            return $this->render('find_contact/index.html.twig', [
                'emails' => null,
                'message' => 'Aucun site enregistré.',
            ]);
        }

        $emails = $this->websiteContactFinderService->findContacts($website);

        foreach ($emails as $email) {

            $exists = false;

            foreach ($company->getCompanyContacts() as $contact) {
                if ($contact->getEmail() === $email) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $contact = new CompanyContacts();

                $contact->setEmail($email);
                $contact->setCompany($company);

                $this->entityManager->persist($contact);
            }
        }

        $this->entityManager->flush();

        return $this->render('find_contact/index.html.twig', [
            'company' => $company,
            'website' => $website,
            'emails' => $emails,
        ]);
    }
}
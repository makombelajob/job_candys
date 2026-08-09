<?php

namespace App\Controller;

use App\Entity\CompanyContacts;
use App\Repository\CompaniesRepository;
use App\Repository\CompanyContactsRepository;
use App\Service\WebsiteContactFinderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;use App\Entity\Companies;
use App\Form\CompanyEditType;
use Symfony\Component\HttpFoundation\Request;

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

        /*
         * On vérifie d'abord si des contacts existent déjà
         * pour cette entreprise.
         */
        $existingContacts = $company->getCompanyContacts();

        if ($existingContacts->count() > 0) {

            // Des contacts existent déjà :
            // on ne relance pas la recherche.
            $emails = [];

            foreach ($existingContacts as $contact) {
                $emails[] = $contact->getEmail();
            }

        } else {

            // Aucun contact existant :
            // on lance la recherche.
            $emails = $this->websiteContactFinderService->findContacts($website) ?? [];

            foreach ($emails as $email) {

                $contact = new CompanyContacts();

                $contact->setEmail($email);
                $contact->setCompany($company);

                $this->entityManager->persist($contact);
            }

            $this->entityManager->flush();
        }

        return $this->render('find_contact/index.html.twig', [
            'company' => $company,
            'website' => $website,
            'emails' => $emails,
        ]);
    }

    
    #[Route('/edit/contact/{siret}', name: 'app_edit_contact')]
    public function edit(
        string $siret,
        Request $request,
        EntityManagerInterface $entityManager,
        CompanyContactsRepository $companyContactsRepository,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $company = $entityManager->getRepository(Companies::class)->findOneBy(['siret' => $siret]);

        if (!$company) {
            throw $this->createNotFoundException('Entreprise introuvable.');
        }

        $form = $this->createForm(CompanyEditType::class, $company);
        $contact = $company->getCompanyContacts()->first();
        if($contact !== false) {
            $form->get('email')->setData($contact->getEmail());

        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $email = trim($form->get('email')->getData());

            $contact = $company->getCompanyContacts()->first();

            if ($contact !== false) {
                // Contact existant : on met simplement à jour l'email
                $contact->setEmail($email);
            } else {
                // Aucun contact : on en crée un
                $contact = new CompanyContacts();
                $contact->setEmail($email);
                $contact->setCompany($company);

                $entityManager->persist($contact);
            }

            $company->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($company);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le contact a été enregistré avec succès.'
            );

            return $this->redirectToRoute('app_spontaneous_application_send', [
                'siret' => $company->getSiret(),
            ]);
        }

        return $this->render('find_contact/edit_contact.html.twig', [
            'form' => $form->createView(),
            'company' => $company,
        ]);
    }

}

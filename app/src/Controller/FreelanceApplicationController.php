<?php

namespace App\Controller;

use App\Form\SearchCompanyType;
use App\Service\InseeApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class FreelanceApplicationController extends AbstractController
{
    #[Route('/freelance/application', name: 'app_freelance_application')]
    public function index(
        Request $request,
        InseeApiService $inseeApiService,
        EntityManagerInterface $entityManager
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_FREELANCE');

        /** @var \App\Entity\Users $user */

        $user = $this->getUser();
        
        $form = $this->createForm(SearchCompanyType::class);

        $form->handleRequest($request);

        $resultats = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $motRecherche = $form->get('mot')->getData();

            if (!empty($motRecherche)) {

                $resultats = $inseeApiService->searchEntreprise(
                    $motRecherche
                );
            }
        }

        return $this->render(
            'freelance_application/index.html.twig',
            [
                'form' => $form->createView(),
                'resultats' => $resultats,
            ]
        );
    }
}

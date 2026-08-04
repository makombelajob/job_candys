<?php

namespace App\Controller;

use App\Form\SearchCompanyType;
use App\Service\InseeApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SpontaneousApplicationController extends AbstractController
{
    #[Route('/spontaneous/application', name: 'app_spontaneous_application')]
    public function index(
        Request $request,
        InseeApiService $inseeApiService
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $form = $this->createForm(SearchCompanyType::class);

        $form->handleRequest($request);

        $resultats = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $motRecherche = $data['mot'];
            if ($motRecherche) {
                $resultats = 
                    $inseeApiService->searchEntreprise(
                        $motRecherche
                    );
            }
        }
        return $this->render(
            'spontaneous_application/index.html.twig',
            [
                'form' => $form->createView(),
                'resultats' => $resultats,
            ]
        );
    }
}
<?php

namespace App\Controller;

use App\Form\CountryTypeForm;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CountryController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index()
    {
        return $this->render('index.html.twig');
    }

    #[Route('/country', name: 'app_country')]
    public function country(Request $request, CountryRepository $countryRepository)
    {

        $query = $request->query->get('search');

        if ($query) {
            $countries = $countryRepository->findBy(['name' => $query]);
        } else {
            $countries = $countryRepository->findAll();
        }

        return $this->render('country/index.html.twig',[
            "countries" => $countries,
        ]);
    }

    #[Route('/country/add', name: 'app_country_add')]
    public function addCountry(Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(CountryTypeForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $country = $form->getData();

            $entityManager->persist($country);
            $entityManager->flush();

            return $this->redirectToRoute('app_country');
        }

        return $this->render('country/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/country/edit/{id}', name: 'app_country_edit')]
    public function editCountry(Request $request, CountryRepository $countryRepository, EntityManagerInterface $entityManager, int $id)
    {
        $country = $countryRepository->find($id);
        if (!$country) {
            throw $this->createNotFoundException('Country not found');
        }

        $form = $this->createForm(CountryTypeForm::class, $country);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_country');
        }

        return $this->render('country/edit.html.twig', [
            'form' => $form->createView(),
            'country' => $country,
        ]);
    }

    #[Route('/country/delete/{id}', name: 'app_country_delete')]
    public function deleteCountry(CountryRepository $countryRepository, EntityManagerInterface $entityManager, int $id)
    {
        $country = $countryRepository->find($id);
        if (!$country) {
            throw $this->createNotFoundException('Country not found');
        }

        $entityManager->remove($country);
        $entityManager->flush();

        return $this->redirectToRoute('app_country');
    }
}
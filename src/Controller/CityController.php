<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Country;
use App\Form\CityTypeForm;
use App\Repository\CityRepository;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CityController extends AbstractController
{

    #[Route('/city', name: 'app_city')]
    public function city(Request $request, CityRepository $cityRepository, CountryRepository $countryRepository)
    {
        $name = $request->query->get('name');
        $status = $request->query->get('status');
        $country = $request->query->get('country');

        $qb = $cityRepository->createQueryBuilder('c')
            ->leftJoin('c.country', 'co')
            ->addSelect('co')
            ->where('c.isDeleted = false');

        if ($name) {
            $qb->andWhere('c.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('c.active = :status')
                ->setParameter('status', (bool) $status);
        }

        if ($country) {
            $qb->andWhere('co.id = :country')
                ->setParameter('country', $country);
        }

        $cities = $qb->getQuery()->getResult();

        return $this->render('city/index.html.twig', [
            'cities' => $cities,
            'filters' => [
                'name' => $name,
                'status' => $status,
                'country' => $country,
            ],
            'countries' => $countryRepository->findAll(),
        ]);
    }

    #[Route('/city/add', name: 'app_city_add')]
    public function addCity(Request $request, EntityManagerInterface $em, CountryRepository $countryRepository, CityRepository $cityRepository)
    {
        if ($request->isMethod('POST')) {
            $countryId = $request->request->get('country');
            $active = $request->request->getBoolean('active');
            $cities = $request->request->all('cities');

            if (!$cities || !is_array($cities) || empty(array_filter($cities))) {
                $this->addFlash('error', 'Please provide at least one city name.');
                return $this->redirectToRoute('app_city_add');
            }

            $country = $countryRepository->find($countryId);
            $errors = [];

            foreach ($cities as $cityName) {
                $existing = $cityRepository->findOneBy([
                    'name' => $cityName,
                    'country' => $country,
                    'isDeleted' => false,
                ]);

                if ($existing) {
                    $errors[] = "City <strong>$cityName</strong> already exists in <strong>{$country->getName()}</strong>.";
                }
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_city_add');
            }

            foreach ($cities as $cityName) {
                $city = new City();
                $city->setName($cityName);
                $city->setCountry($country);
                $city->setActive($active);
                $em->persist($city);
            }

            $em->flush();

            $this->addFlash('success', 'Cities added successfully.');
            return $this->redirectToRoute('app_city');
        }

        return $this->render('city/add.html.twig', [
            'countries' => $countryRepository->findAll(),
        ]);
    }

    #[Route('/city/edit/{id}', name: 'app_city_edit')]
    public function editCity(Request $request, CityRepository $cityRepository, EntityManagerInterface $entityManager, int $id)
    {
        $city = $cityRepository->find($id);
        if (!$city) {
            throw $this->createNotFoundException('City not found');
        }

        $form = $this->createForm(CityTypeForm::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'City updated successfully.');
            return $this->redirectToRoute('app_city');
        }

        return $this->render('city/edit.html.twig', [
            'form' => $form->createView(),
            'city' => $city,
        ]);
    }

    #[Route('/city/delete/{id}', name: 'app_city_delete')]
    public function deleteCity(CityRepository $cityRepository, EntityManagerInterface $entityManager, int $id)
    {
        $city = $cityRepository->find($id);
        if (!$city) {
            throw $this->createNotFoundException('City not found');
        }

        $city->setIsDeleted(true);
        $entityManager->flush();

        $this->addFlash('success', 'City deleted successfully.');
        return $this->redirectToRoute('app_city');
    }
}

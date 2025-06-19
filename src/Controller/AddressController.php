<?php

namespace App\Controller;

use App\Entity\Country;
use App\Form\AddressTypeForm;
use App\Repository\AddressRepository;
use App\Repository\CityRepository;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AddressController extends AbstractController
{
    #[Route('/address', name: 'app_address')]
    public function index(AddressRepository $addressRepository, Request $request, CountryRepository $countryRepository, CityRepository $cityRepository)
    {
        $name = $request->query->get('name');
        $city = $request->query->get('city');
        $country = $request->query->get('country');

        $qb = $addressRepository->createQueryBuilder('c')
            ->leftJoin('c.country', 'co')
            ->leftJoin('c.city', 'cc')
            ->addSelect('cc')
            ->addSelect('co');

        if ($name) {
            $qb->andWhere('c.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        if ($city) {
            $qb->andWhere('co.id = :city')
                ->setParameter('city', $city);
        }
    
        if ($country) {
            $qb->andWhere('co.id = :country')
                ->setParameter('country', $country);
        }
        $addresses = $qb->getQuery()->getResult();

        return $this->render('address/index.html.twig', [
            "addresses" => $addresses,
            'filters' => [
                'name' =>  $name,
                'city' => $city,
                "country" => $country
            ],
            'cities' => $cityRepository->findAll(),
            'countries' => $countryRepository->findAll(),
        ]);
    }

    #[Route('/address/add', name: 'app_address_add')]
    public function addAddress(Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(AddressTypeForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $address = $form->getData();
            $entityManager->persist($address);
            $entityManager->flush();

            return $this->redirectToRoute('app_address');
        }

        return $this->render('address/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/address/edit/{id}','app_address_edit')]
    public function editAddress(Request $request, EntityManagerInterface $entityManager, AddressRepository $addressRepository, $id)
    {
        $address = $addressRepository->find($id);

        if (!$address) {
            throw $this->createNotFoundException('Address Not found!');
        }

        $form = $this->createForm(AddressTypeForm::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Address Updated Successfully');
            return $this->redirectToRoute('app_address');
        }

        return $this->render('address/edit.html.twig',[
            'form' => $form->createView(),
            'address' => $address,
        ]);
    }

    #[Route('/address/delete/{id}', 'app_address_delete')]
    public function deleteAddress(AddressRepository $addressRepository, EntityManagerInterface $entityManager, $id)
    {
        $id = $addressRepository->find($id);
        
        $entityManager->remove($id);
        $entityManager->flush();

        $this->addFlash('success', 'Address Deleted Successfully');

        return $this->redirectToRoute('app_address');
    }

    #[Route('/get-cities/{id}', name: 'get_cities')]
    public function getCities(Country $country): JsonResponse
    {
        $cities = $country->getCities();
        // dd($cities);
        $data = [];
        foreach ($cities as $city) {
            $data[] = ['id' => $city->getId(), 'name' => $city->getName()];
        }

        return new JsonResponse($data);
    }
}

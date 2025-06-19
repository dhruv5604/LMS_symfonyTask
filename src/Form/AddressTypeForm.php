<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\Country;
use App\Entity\City;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'placeholder' => 'Select a country',
                'choice_label' => 'name',
                'required' => true
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'placeholder' => 'Select a city',
                'choice_label' => 'name',
                'choices' => [],
                'required' => true
            ])
            ->add('streetName', TextareaType::class, [
                'required' => true,
            ])
            ->add('postcode', TextType::class, [
                'required' => true,
                'attr' => ['pattern' => '\d*'],
            ])
        ;

        $formModifier = function ($form, Country $country = null) {
            $cities = $country ? $country->getCities() : [];
            $form->add('city', EntityType::class, [
                'class' => City::class,
                'choices' => $cities,
                'choice_label' => 'name',
                'placeholder' => 'Select a city',
                'required' => true
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data?->getCountry());
            }
        );

        $builder->get('country')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event->getForm()->getParent(), $event->getForm()->getData());
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Address::class]);
    }
}

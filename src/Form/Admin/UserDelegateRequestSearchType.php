<?php

namespace App\Form\Admin;

use App\Entity\City;
use App\Entity\School;
use App\Entity\UserDelegateRequest;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserDelegateRequestSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('GET')
            ->add('firstName', TextType::class, [
                'required' => false,
                'label' => 'Ime',
            ])
            ->add('lastName', TextType::class, [
                'required' => false,
                'label' => 'Prezime',
            ])
            ->add('email', TextType::class, [
                'required' => false,
                'label' => 'Email',
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'Broj telefona',
            ])
            ->add('city', EntityType::class, [
                'required' => false,
                'class' => City::class,
                'placeholder' => '',
                'label' => 'Grad',
                'choice_value' => 'id',
                'choice_label' => function (City $city): string {
                    return $city->getName();
                },
            ])
            ->add('school', EntityType::class, [
                'required' => false,
                'class' => School::class,
                'placeholder' => '',
                'label' => 'Škola',
                'choice_value' => 'id',
                'choice_label' => function (School $school): string {
                    return $school->getName().' ('.$school->getCity()->getName().')';
                },
            ])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'label' => 'Status',
                'choices' => array_flip(UserDelegateRequest::STATUS),
            ])
            ->add('submit', SubmitType::class, [
                'label' => '<i class="ti ti-search text-2xl"></i> Pretraži',
                'label_html' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}

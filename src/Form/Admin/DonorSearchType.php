<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DonorSearchType extends AbstractType
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
            ->add('isMonthly', ChoiceType::class, [
                'required' => false,
                'label' => 'Mesečna podrška',
                'choices' => [
                    'Da' => true,
                    'Ne' => false,
                ],
            ])
            ->add('onlyToUniversity', ChoiceType::class, [
                'required' => false,
                'label' => 'Doniraju samo univerzitetima',
                'choices' => [
                    'Da' => true,
                    'Ne' => false,
                ],
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

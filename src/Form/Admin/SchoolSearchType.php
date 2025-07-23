<?php

namespace App\Form\Admin;

use App\Entity\City;
use App\Entity\SchoolType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SchoolSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('GET')
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Naziv',
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'required' => false,
                'placeholder' => '',
                'label' => 'Grad',
                'choice_value' => 'id',
                'choice_label' => 'name',
            ])
            ->add('type', EntityType::class, [
                'class' => SchoolType::class,
                'required' => false,
                'placeholder' => '',
                'label' => 'Tip škole',
                'choice_value' => 'id',
                'choice_label' => 'name',
            ])
            ->add('processing', ChoiceType::class, [
                'required' => false,
                'label' => 'Instrukcije se kreiraju?',
                'choices' => [
                    'Da' => true,
                    'Ne' => false,
                ],
            ])
            ->add('havePayoutPriority', ChoiceType::class, [
                'required' => false,
                'label' => 'Ima prioritet za kreiranje instrukcija?',
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

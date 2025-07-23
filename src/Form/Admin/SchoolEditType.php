<?php

namespace App\Form\Admin;

use App\Entity\City;
use App\Entity\School;
use App\Entity\SchoolType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SchoolEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Naziv',
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'placeholder' => '',
                'label' => 'Grad',
                'choice_value' => 'id',
                'choice_label' => 'name',
            ])
            ->add('type', EntityType::class, [
                'class' => SchoolType::class,
                'placeholder' => '',
                'label' => 'Tip škole',
                'choice_value' => 'id',
                'choice_label' => 'name',
            ])
            ->add('processing', CheckboxType::class, [
                'label' => 'Kreiranje instrukcija?',
                'required' => false,
            ])
            ->add('havePayoutPriority', CheckboxType::class, [
                'label' => 'Ima prioritet za kreiranje instrukcija?',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => School::class,
        ]);
    }
}

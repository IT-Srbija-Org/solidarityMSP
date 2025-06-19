<?php

namespace App\Form\Admin;

use App\Entity\City;
use App\Entity\DamagedEducator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DamagedEducatorEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('period', TextType::class, [
                'disabled' => true,
                'mapped' => false,
                'label' => 'Period',
                'data' => $options['data']->getPeriod()?->getChoiceLabel(),
            ])
            ->add('name', TextType::class, [
                'label' => 'Ime',
            ])
            ->add('school', TextType::class, [
                'disabled' => true,
                'mapped' => false,
                'label' => 'Škola',
                'data' => $options['data']->getSchool()?->getName(),
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'placeholder' => '',
                'label' => 'Grad (Prebivalište oštećenog)',
                'choice_value' => 'id',
                'choice_label' => 'name',
            ])
            ->add('amount', IntegerType::class, [
                'label' => 'Cifra',
                'attr' => [
                    'min' => 500,
                    'max' => DamagedEducator::MONTHLY_LIMIT,
                ],
            ])
            ->add('accountNumber', TextType::class, [
                'label' => 'Broj računa',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DamagedEducator::class,
        ]);
    }
}

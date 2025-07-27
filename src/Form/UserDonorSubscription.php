<?php

namespace App\Form;

use App\Entity\UserDonor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserDonorSubscription extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', IntegerType::class, [
                'label' => 'Iznos',
                'attr' => [
                    'min' => 500,
                    'max' => 300000,
                ],
            ])
            ->add('schoolType', ChoiceType::class, [
                'choices' => array_flip(UserDonor::SCHOOL_TYPES),
                'label' => 'Kome želiš da doniraš?',
                'placeholder' => '',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserDonor::class,
        ]);
    }
}

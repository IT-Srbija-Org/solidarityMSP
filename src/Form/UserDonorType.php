<?php

namespace App\Form;

use App\Entity\UserDonor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserDonorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isMonthly', ChoiceType::class, [
                'label' => 'Mesečna podrška',
                'placeholder' => '',
                'choices' => [
                    'Ne' => false,
                    'Da' => true,
                ],
            ])
            ->add('amount', IntegerType::class, [
                'label' => 'Iznos',
                'attr' => [
                    'placeholder' => '500',
                    'min' => 500,
                ],
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'label' => 'Komentar (opciono)',
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

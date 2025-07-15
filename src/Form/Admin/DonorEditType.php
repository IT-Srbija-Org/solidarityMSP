<?php

namespace App\Form\Admin;

use App\Entity\UserDonor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DonorEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'disabled' => true,
                'mapped' => false,
                'label' => 'Ime',
                'data' => $options['user'] ? $options['user']->getFullName() : null,
            ])
            ->add('email', TextType::class, [
                'disabled' => true,
                'mapped' => false,
                'label' => 'Email',
                'data' => $options['user'] ? $options['user']->getEmail() : null,
            ])
            ->add('isMonthly', CheckboxType::class, [
                'required' => false,
                'label' => 'Mesečna podrška',
            ])
//            ->add('schoolType', ChoiceType::class, [
//                'choices' => array_flip(UserDonor::SCHOOL_TYPES),
//                'label' => 'Kome želiš da doniraš?',
//                'placeholder' => '',
//            ])
            ->add('amount', IntegerType::class, [
                'label' => 'Iznos',
                'attr' => [
                    'placeholder' => '500',
                    'min' => 500,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserDonor::class,
            'user' => null,
        ]);
    }
}

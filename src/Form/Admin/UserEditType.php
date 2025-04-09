<?php

namespace App\Form\Admin;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Ime',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Prezime',
            ])
            ->add('email', null, [
                'disabled' => true,
                'label' => 'Email',
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Privilegije',
                'choices' => array_flip(User::ROLES),
                'expanded' => true,
                'multiple' => true,
                'disabled' => false,
                'choice_attr' => function ($choice, $key, $value) {
                    if ('ROLE_USER' === $value) {
                        return ['disabled' => 'disabled'];
                    }

                    return [];
                },
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Aktivan',
                'choices' => [
                    'Da' => true,
                    'Ne' => false,
                ],
            ])
            ->add('isVerified', ChoiceType::class, [
                'label' => 'Verifikovan',
                'choices' => [
                    'Da' => true,
                    'Ne' => false,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

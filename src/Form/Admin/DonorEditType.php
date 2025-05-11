<?php

namespace App\Form\Admin;

use App\Entity\UserDonor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
            ->add('amount', IntegerType::class, [
                'label' => 'Iznos',
                'attr' => [
                    'placeholder' => '500',
                    'min' => 500,
                ],
            ])
            ->add('how', ChoiceType::class, [
                'label' => 'Kako ste saznali za Mrežu Solidarnosti?',
                'choices' => [
                    'Čuo/la sam na televiziji' => 1,
                    'Saznao/la preko društvenih mreža' => 2,
                    'Preko člana porodice/prijatelja' => 3,
                    'Preko news portala / foruma / Reditta' => 4,
                    'Preko škole, fakulteta…' => 5,
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

<?php

namespace App\Form;

use App\Entity\UserDonor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserDonorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
                'mapped' => false,
                'label' => 'Email',
                'data' => $options['user'] ? $options['user']->getEmail() : null,
                'disabled' => $options['user'] ? true : false,
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
                'required' => false,
                'choices' => [
                    'Čuo/la sam na televiziji' => 1,
                    'Saznao/la preko društvenih mreža' => 2,
                    'Preko člana porodice/prijatelja' => 3,
                    'Preko news portala / foruma / Reditta' => 4,
                    'Preko škole, fakulteta…' => 5,
                ],
                'label' => 'Kako ste saznali za Mrežu Solidarnosti?',
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
            'user' => null,
        ]);
    }
}

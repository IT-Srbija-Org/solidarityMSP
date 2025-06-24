<?php

namespace App\Form;

use App\Entity\UserDonor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserDonorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'required' => true,
                'mapped' => false,
                'label' => 'Ime',
                'data' => $options['user'] ? $options['user']->getFirstName() : null,
            ])
            ->add('lastName', TextType::class, [
                'required' => true,
                'mapped' => false,
                'label' => 'Prezime',
                'data' => $options['user'] ? $options['user']->getLastName() : null,
            ])
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
            ->add('comment', TextareaType::class, [
                'required' => false,
                'label' => 'Komentar (opciono)',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event): void {
                $form = $event->getForm();
                $userDonor = $event->getData();
                if (!$userDonor || !$userDonor->getUser() || !$userDonor->getUser()->getUserDonor()) {
                    $form->add('comesFrom', ChoiceType::class, [
                        'choices' => array_flip(UserDonor::COMES_FROM),
                        'label' => 'Kako ste saznali za Mrežu solidarnosti?',
                    ]);
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserDonor::class,
            'user' => null,
        ]);
    }
}

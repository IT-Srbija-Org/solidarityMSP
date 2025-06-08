<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileTransactionConfirmPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'required' => true,
                'label' => 'Vaše ime',
                'data' => $options['user'] ? $options['user']->getFirstName() : null,
            ])
            ->add('lastName', TextType::class, [
                'required' => true,
                'label' => 'Vaše prezime',
                'data' => $options['user'] ? $options['user']->getLastName() : null,
            ])
            ->add('confirm', CheckboxType::class, [
                'label' => 'Potvrđujem da sam izvršio uplatu',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Potvrdi',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
        ]);
    }
}

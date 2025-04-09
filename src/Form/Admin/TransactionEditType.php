<?php

namespace App\Form\Admin;

use App\Entity\Transaction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', TextType::class, [
                'disabled' => true,
                'mapped' => false,
                'data' => $options['data']->getUser()?->getFullName(),
                'label' => 'Donator',
            ])
            ->add('educator', TextType::class, [
                'disabled' => true,
                'mapped' => false,
                'data' => $options['data']->getEducator()?->getName(),
                'label' => 'Edukator',
            ])
            ->add('accountNumber', TextType::class, [
                'disabled' => true,
                'mapped' => false,
                'data' => $options['data']->getEducator()?->getAccountNumber(),
                'label' => 'Broj računa',
            ])
            ->add('amount', TextType::class, [
                'disabled' => true,
                'label' => 'Cifra',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => array_flip(Transaction::STATUS),
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}

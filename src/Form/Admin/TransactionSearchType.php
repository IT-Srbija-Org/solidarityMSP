<?php

namespace App\Form\Admin;

use App\Entity\Transaction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('GET')
            ->add('id', IntegerType::class, [
                'required' => false,
                'label' => 'ID',
            ])
            ->add('donor', TextType::class, [
                'required' => false,
                'label' => 'Donator (Email)',
            ])
            ->add('educator', TextType::class, [
                'required' => false,
                'label' => 'Oštećeni',
            ])
            ->add('accountNumber', TextType::class, [
                'required' => false,
                'label' => 'Broj računa',
            ])
            ->add('isUserDonorConfirmed', ChoiceType::class, [
                'required' => false,
                'label' => 'Donator je potvrdio uplatu?',
                'choices' => [
                    'Da' => true,
                    'Ne' => false,
                ],
            ])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'choices' => array_flip(Transaction::STATUS),
                'label' => 'Status',
            ])
            ->add('submit', SubmitType::class, [
                'label' => '<i class="ti ti-search text-2xl"></i> Pretraži',
                'label_html' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}

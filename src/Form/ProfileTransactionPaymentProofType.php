<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class ProfileTransactionPaymentProofType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('paymentProofFile', FileType::class, [
                'label' => 'Uplatnica (PDF, JPG, PNG)',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1M',
                        'maxSizeMessage' => 'Dokument ne sme biti preko 1M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'Molimo uploadajte validan dokument (PDF ili sliku)',
                    ]),
                ],
                'attr' => [
                    'class' => 'file-input file-input-bordered w-full',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);
    }
}

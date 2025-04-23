<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class ProfileTransactionConfirmPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('confirm', CheckboxType::class, [
                'label' => 'Potvrđujem da sam izvršio uplatu',
                'required' => true,
            ])
            ->add('file', FileType::class, [
                'required' => false,
                'label' => 'Uplatnica (PDF, JPG, PNG)',
                'constraints' => [
                    new File([
                        'maxSize' => '1M',
                        'maxSizeMessage' => 'Dokument ne sme biti preko 1M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Molimo uploadajte validan dokument (PDF, JPG, PNG ili WEBP)',
                    ]),
                ],
                'attr' => [
                    'class' => 'file-input file-input-bordered w-full',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Potvrdi',
            ]);
    }
}

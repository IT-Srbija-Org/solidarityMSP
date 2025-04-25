<?php

namespace App\Form;

use App\Entity\School;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DamagedEducatorImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $schools = $options['entityManager']->getRepository(School::class)
            ->createQueryBuilder('s')
            ->innerJoin('s.userDelegateSchools', 'uds')
            ->where('uds.user = :user')
            ->setParameter('user', $options['user'])
            ->getQuery()
            ->getResult();

        $schoolChoices = [];
        foreach ($schools as $school) {
            $schoolChoices[$school->getName().' ('.$school->getCity()->getName().')'] = $school;
        }

        $builder
            ->add('school', ChoiceType::class, [
                'placeholder' => 1 === count($schoolChoices) ? null : '',
                'label' => 'Škola',
                'choices' => $schoolChoices,
                'choice_value' => 'id',
            ])
            ->add('file', FileType::class, [
                'label' => '<a href="/file/primer.xlsx" target="_blank" class="link link-primary link-hover">Primer fajla</a>',
                'constraints' => [
                    new File([
                        'maxSize' => '3M',
                        'mimeTypes' => [
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ],
                        'mimeTypesMessage' => 'Molimo upload-ujte isključivo  fajl (.xls ili .xlsx)',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
            'entityManager' => null,
        ]);
    }
}

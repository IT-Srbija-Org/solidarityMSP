<?php

namespace App\Form\Admin;

use App\Entity\City;
use App\Entity\DamagedEducatorPeriod;
use App\Entity\School;
use App\Entity\Transaction;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
            ->add('period', EntityType::class, [
                'required' => false,
                'class' => DamagedEducatorPeriod::class,
                'placeholder' => '',
                'label' => 'Period',
                'choice_value' => 'id',
                'choice_label' => function (DamagedEducatorPeriod $damagedEducatorPeriod): string {
                    $month = $damagedEducatorPeriod->getDate()->format('M');

                    $type = match ($damagedEducatorPeriod->getType()) {
                        DamagedEducatorPeriod::TYPE_FIRST_HALF => ' (1/2)',
                        DamagedEducatorPeriod::TYPE_SECOND_HALF => ' (2/2)',
                        default => '',
                    };

                    return $month.$type.', '.$damagedEducatorPeriod->getYear();
                },
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.year', 'DESC')
                        ->addOrderBy('s.month', 'DESC')
                        ->addOrderBy('s.id', 'DESC');
                },
            ])
            ->add('donor', TextType::class, [
                'required' => false,
                'label' => 'Donator (Email)',
            ])
            ->add('educator', TextType::class, [
                'required' => false,
                'label' => 'Oštećeni',
            ])
            ->add('city', EntityType::class, [
                'required' => false,
                'class' => City::class,
                'placeholder' => '',
                'label' => 'Grad',
                'choice_value' => 'id',
                'choice_label' => function (City $city): string {
                    return $city->getName();
                },
            ])
            ->add('school', EntityType::class, [
                'required' => false,
                'class' => School::class,
                'placeholder' => '',
                'label' => 'Škola',
                'choice_value' => 'id',
                'choice_label' => function (School $school): string {
                    return $school->getName().' ('.$school->getCity()->getName().')';
                },
            ])
            ->add('accountNumber', TextType::class, [
                'required' => false,
                'label' => 'Broj računa',
            ])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'choices' => array_flip(Transaction::STATUS),
                'label' => 'Status',
            ])
            ->add('hasPaymentProofFile', ChoiceType::class, [
                'required' => false,
                'label' => 'Ima potvrdu o uplati?',
                'choices' => [
                    'Da' => true,
                    'Ne' => false,
                ],
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

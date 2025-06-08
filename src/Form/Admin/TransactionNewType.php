<?php

namespace App\Form\Admin;

use App\Entity\DamagedEducator;
use App\Entity\DamagedEducatorPeriod;
use App\Entity\School;
use App\Entity\Transaction;
use App\Validator\UserDonorExists;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionNewType extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('period', EntityType::class, [
                'mapped' => false,
                'class' => DamagedEducatorPeriod::class,
                'placeholder' => '',
                'label' => 'Period',
                'choice_value' => 'id',
                'choice_label' => function (DamagedEducatorPeriod $damagedEducatorPeriod): string {
                    return $damagedEducatorPeriod->getChoiceLabel();
                },
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.year', 'DESC')
                        ->addOrderBy('s.month', 'DESC')
                        ->addOrderBy('s.id', 'DESC');
                },
            ])
            ->add('userDonorEmail', EmailType::class, [
                'mapped' => false,
                'label' => 'Email donatora',
                'constraints' => [
                    new UserDonorExists(),
                ],
            ])
            ->add('school', EntityType::class, [
                'mapped' => false,
                'class' => School::class,
                'placeholder' => '',
                'label' => 'Škola',
                'choice_value' => 'id',
                'choice_label' => function (School $school): string {
                    return $school->getName().' ('.$school->getCity()->getName().')';
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->innerJoin(DamagedEducator::class, 'de', 'WITH', 'de.school = s')
                        ->groupBy('s.id')
                        ->orderBy('s.name', 'ASC');
                },
            ])
            ->add('damagedEducator', ChoiceType::class, [
                'label' => 'Oštećeni',
                'disabled' => true,
            ])
            ->add('amount', IntegerType::class, [
                'label' => 'Iznos',
                'attr' => [
                    'min' => 0,
                    'max' => 60000,
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => array_flip(Transaction::STATUS),
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);

        // Add event listeners for dynamic damagedEducator field
        $formModifier = function (FormInterface $form, ?DamagedEducatorPeriod $period, ?School $school) {
            $choices = [];

            if ($period && $school) {
                $choices = $this->entityManager->getRepository(DamagedEducator::class)->findBy([
                    'period' => $period,
                    'school' => $school,
                ]);
            }

            $form->add('damagedEducator', ChoiceType::class, [
                'label' => 'Oštećeni',
                'choices' => $choices,
                'choice_value' => 'id',
                'choice_label' => function (?DamagedEducator $damagedEducator) {
                    return $damagedEducator ? $damagedEducator->getName().' ('.$damagedEducator->getAccountNumber().')' : '';
                },
                'placeholder' => '',
                'disabled' => !$period || !$school,
            ]);
        };

        // Event listener for when period or school changes
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $form = $event->getForm();
                $period = $form->has('period') ? $form->get('period')->getData() : null;
                $school = $form->has('school') ? $form->get('school')->getData() : null;
                $formModifier($form, $period, $school);
            }
        );

        // Event listener for when period is submitted
        $builder->get('period')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $period = $event->getForm()->getData();
                $form = $event->getForm()->getParent();
                $school = $form->has('school') ? $form->get('school')->getData() : null;
                $formModifier($form, $period, $school);
            }
        );

        // Event listener for when school is submitted
        $builder->get('school')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $school = $event->getForm()->getData();
                $form = $event->getForm()->getParent();
                $period = $form->has('period') ? $form->get('period')->getData() : null;
                $formModifier($form, $period, $school);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}

<?php

namespace App\Form\Admin;

use App\Entity\DamagedEducator;
use App\Entity\DamagedEducatorPeriod;
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
        $educators = $this->entityManager->getRepository(DamagedEducator::class)->findBy([]);

        $builder
            ->add('userDonorEmail', EmailType::class, [
                'mapped' => false,
                'label' => 'Email donatora',
                'constraints' => [
                    new UserDonorExists(),
                ],
            ])
            ->add('damagedEducator', ChoiceType::class, [
                'label' => 'Oštećeni',
                'choices' => $educators,
                'choice_value' => 'id',
                'choice_label' => function (?DamagedEducator $damagedEducator) {
                    return $damagedEducator ? $damagedEducator->getName().' ('.$damagedEducator->getAccountNumber().')' : '';
                },
                'placeholder' => '',
//                'disabled' => true,
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}

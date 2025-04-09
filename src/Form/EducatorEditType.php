<?php

namespace App\Form;

use App\Entity\Educator;
use App\Entity\School;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EducatorEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Ime',
            ])
            ->add('school', EntityType::class, [
                'class' => School::class,
                'placeholder' => '',
                'label' => 'Škola',
                'query_builder' => function (EntityRepository $er) use ($options): QueryBuilder {
                    return $er->createQueryBuilder('s')
                        ->innerJoin('s.userDelegateSchools', 'uds')
                        ->where('uds.user = :user')
                        ->setParameter('user', $options['user']);
                },
                'choice_value' => 'id',
                'choice_label' => function (School $school): string {
                    return $school->getName().' ('.$school->getCity()->getName().')';
                },
            ])
            ->add('amount', IntegerType::class, [
                'label' => 'Cifra',
            ])
            ->add('accountNumber', IntegerType::class, [
                'label' => 'Broj računa',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Educator::class,
            'user' => null,
        ]);
    }
}

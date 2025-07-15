<?php

namespace App\Form\Admin;

use App\Entity\DamagedEducator;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DamagedEducatorSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('GET')
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Ime',
            ])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'label' => 'Status',
                'choices' => array_flip(DamagedEducator::STATUS),
            ])
            ->add('accountNumber', TextType::class, [
                'required' => false,
                'label' => 'Broj računa',
            ])
            ->add('createdBy', EntityType::class, [
                'required' => false,
                'class' => User::class,
                'placeholder' => '',
                'label' => 'Delegat',
                'choice_value' => 'id',
                'choice_label' => function (User $user): string {
                    return $user->getFullName().' ('.$user->getEmail().')';
                },
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('u')
                        ->where('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_DELEGATE%');
                },
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

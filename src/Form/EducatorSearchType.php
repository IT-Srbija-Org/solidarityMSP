<?php

namespace App\Form;

use App\Entity\School;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EducatorSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('GET')
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Ime',
            ])
            ->add('school', EntityType::class, [
                'required' => false,
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
            'user' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}

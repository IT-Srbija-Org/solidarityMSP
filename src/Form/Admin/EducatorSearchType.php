<?php

namespace App\Form\Admin;

use App\Entity\School;
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
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}

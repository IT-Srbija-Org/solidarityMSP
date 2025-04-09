<?php

namespace App\Form\Admin;

use App\Entity\School;
use App\Entity\UserDelegateSchool;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserDelegateSchoolConnectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('school', EntityType::class, [
                'class' => School::class,
                'placeholder' => '',
                'label' => 'Škola',
                'choice_value' => 'id',
                'choice_label' => function (School $school): string {
                    return $school->getName().' ('.$school->getCity()->getName().')';
                },
            ])
            ->add('submit', SubmitType::class, [
                'label' => '<i class="ti ti-plus text-xl"></i> Dodaj',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserDelegateSchool::class,
        ]);
    }
}

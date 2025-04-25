<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DamagedEducatorDeleteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('confirm', CheckboxType::class, [
                'label' => 'Potvrđujem da želim da obrisem oštećenog "'.$options['damagedEducator']->getName().'"',
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Komentar',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Potvrdi',
                'attr' => [
                    'class' => 'btn btn-error',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'damagedEducator' => null,
        ]);
    }
}

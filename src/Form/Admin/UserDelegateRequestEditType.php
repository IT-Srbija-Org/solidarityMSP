<?php

namespace App\Form\Admin;

use App\Entity\City;
use App\Entity\School;
use App\Entity\SchoolType;
use App\Entity\UserDelegateRequest;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserDelegateRequestEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', TextType::class, [
                'data' => $options['data']->getUser()?->getFullName(),
                'label' => 'Ime',
                'disabled' => true,
                'mapped' => false,
            ])
            ->add('email', TextType::class, [
                'data' => $options['data']->getUser()?->getEmail(),
                'label' => 'Email',
                'disabled' => true,
                'mapped' => false,
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'Broj telefona',
            ])
            ->add('schoolType', EntityType::class, [
                'class' => SchoolType::class,
                'placeholder' => '',
                'label' => 'Tip obrazovne ustanove',
                'choice_value' => 'id',
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'select select-md select-bordered rounded-md w-full md:max-w-xl',
                ],
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'placeholder' => '',
                'label' => 'Mesto škole',
                'choice_value' => 'id',
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'select select-md select-bordered rounded-md w-full md:max-w-xl',
                ],
            ])
            ->add('school', EntityType::class, [
                'class' => School::class,
                'placeholder' => '',
                'label' => 'Škola',
                'choice_value' => 'id',
                'choice_label' => 'name',
            ])
            ->add('totalEducators', IntegerType::class, [
                'required' => false,
                'label' => 'Ukupan broj zaposlenih u školi',
            ])
            ->add('totalBlockedEducators', IntegerType::class, [
                'required' => false,
                'label' => 'Ukupno u obustavi',
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'disabled' => true,
                'label' => 'Komentar (opciono)',
            ])
            ->add('adminComment', TextareaType::class, [
                'required' => false,
                'label' => 'Admin komentar (opciono)',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => array_flip(UserDelegateRequest::STATUS),
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserDelegateRequest::class,
        ]);
    }
}

<?php

namespace App\Form\Admin;

use App\Entity\UserDelegateRequest;
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
                'label' => 'Broj telefona',
            ])
            ->add('schoolType', TextType::class, [
                'data' => $options['data']->getSchoolType()?->getName(),
                'label' => 'Tip obrazovne ustanove',
                'disabled' => true,
                'mapped' => false,
            ])
            ->add('city', TextType::class, [
                'data' => $options['data']->getCity()?->getName(),
                'label' => 'Mesto škole',
                'disabled' => true,
                'mapped' => false,
            ])
            ->add('school', TextType::class, [
                'data' => $options['data']->getSchool()?->getName(),
                'label' => 'Škola',
                'disabled' => true,
                'mapped' => false,
            ])
            ->add('totalEducators', IntegerType::class, [
                'label' => 'Ukupan broj zaposlenih u školi',
                'disabled' => true,
            ])
            ->add('totalBlockedEducators', IntegerType::class, [
                'label' => 'Ukupno u obustavi',
                'disabled' => true,
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

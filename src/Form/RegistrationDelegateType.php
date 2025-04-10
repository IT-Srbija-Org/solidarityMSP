<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\School;
use App\Entity\SchoolType;
use App\Entity\UserDelegateRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationDelegateType extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('phone', TextType::class, [
                'label' => 'Vaš broj telefona',
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
            ->add('totalEducators', IntegerType::class, [
                'label' => 'Ukupan broj zaposlenih u školi',
            ])
            ->add('totalBlockedEducators', IntegerType::class, [
                'label' => 'Ukupno u obustavi',
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'label' => 'Komentar (opciono)',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Pošalji',
            ]);

        // Add event listener to dynamically modify the school field
        $formModifier = function (FormInterface $form, ?City $city) {
            $schools = $city ? $this->entityManager->getRepository(School::class)->findBy(['city' => $city]) : [];

            $form->add('school', EntityType::class, [
                'class' => School::class,
                'placeholder' => $city ? 'Izaberite školu' : 'Izaberite prvo mesto',
                'label' => 'Škola',
                'choice_label' => 'name',
                'choices' => $schools,
                'attr' => [
                    'class' => 'select select-md select-bordered rounded-md w-full md:max-w-xl',
                ],
            ]);
        };

        $builder->get('city')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $city = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $city);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $city = $data->getCity() ?? null;
                $formModifier($event->getForm(), $city);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserDelegateRequest::class,
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\DamagedEducator;
use App\Entity\School;
use App\Form\DataTransformer\AccountNumberTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DamagedEducatorEditType extends AbstractType
{
    private AccountNumberTransformer $accountNumberTransformer;

    public function __construct()
    {
        $this->accountNumberTransformer = new AccountNumberTransformer();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $schools = $options['entityManager']->getRepository(School::class)
            ->createQueryBuilder('s')
            ->innerJoin('s.userDelegateSchools', 'uds')
            ->where('uds.user = :user')
            ->setParameter('user', $options['user'])
            ->getQuery()
            ->getResult();

        $schoolChoices = [];
        foreach ($schools as $school) {
            $schoolChoices[$school->getName().' ('.$school->getCity()->getName().')'] = $school;
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Ime',
            ])
            ->add('school', ChoiceType::class, [
                'placeholder' => 1 === count($schoolChoices) ? null : '',
                'label' => 'Škola',
                'choices' => $schoolChoices,
                'choice_value' => 'id',
            ])
            ->add('amount', IntegerType::class, [
                'label' => 'Cifra',
            ])
            ->add('accountNumber', TextType::class, [
                'label' => 'Broj računa',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);

        $builder->get('accountNumber')->addModelTransformer($this->accountNumberTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DamagedEducator::class,
            'user' => null,
            'entityManager' => null,
        ]);
    }
}

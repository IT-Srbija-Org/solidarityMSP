<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Entity\UserDonor;
use App\Form\UserDonorType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Test\TypeTestCase;

class UserDonorTypeTest extends TypeTestCase
{
    public function testUserDonorFormHasCorrectFields(): void
    {
        $form = $this->factory->create(UserDonorType::class);

        // Check that form contains the expected fields
        $this->assertTrue($form->has('isMonthly'));
        $this->assertTrue($form->has('amount'));
        $this->assertTrue($form->has('comment'));
        $this->assertTrue($form->has('submit'));

        // Get the form field types
        $this->assertInstanceOf(ChoiceType::class, $form->get('isMonthly')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(IntegerType::class, $form->get('amount')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextareaType::class, $form->get('comment')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(SubmitType::class, $form->get('submit')->getConfig()->getType()->getInnerType());
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'isMonthly' => true,
            'amount' => 5000,
            'comment' => 'Test donation comment',
        ];

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');

        $userDonor = new UserDonor();
        $userDonor->setUser($user);

        $form = $this->factory->create(UserDonorType::class, $userDonor);

        // Submit the form with test data
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        // Check that the form is valid
        $this->assertTrue($form->isValid());

        // Check that the form data was mapped to the entity
        $this->assertTrue($userDonor->isMonthly());
        $this->assertEquals(5000, $userDonor->getAmount());
        $this->assertEquals('Test donation comment', $userDonor->getComment());
    }

    public function testConfigureOptions(): void
    {
        $form = $this->factory->create(UserDonorType::class);
        $options = $form->getConfig()->getOptions();

        // Test that data_class is set to UserDonor::class
        $this->assertEquals(UserDonor::class, $options['data_class']);
    }
}

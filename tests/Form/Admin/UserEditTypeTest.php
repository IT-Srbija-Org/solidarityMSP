<?php

namespace App\Tests\Form\Admin;

use App\Entity\User;
use App\Form\Admin\UserEditType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

class UserEditTypeTest extends TypeTestCase
{
    public function testUserEditFormHasCorrectFields(): void
    {
        $form = $this->factory->create(UserEditType::class);

        // Check that form contains the expected fields
        $this->assertTrue($form->has('firstName'));
        $this->assertTrue($form->has('lastName'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('roles'));
        $this->assertTrue($form->has('isActive'));
        $this->assertTrue($form->has('isVerified'));
        $this->assertTrue($form->has('submit'));

        // Get the form field types
        $this->assertInstanceOf(TextType::class, $form->get('firstName')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextType::class, $form->get('lastName')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(ChoiceType::class, $form->get('roles')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(ChoiceType::class, $form->get('isActive')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(ChoiceType::class, $form->get('isVerified')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(SubmitType::class, $form->get('submit')->getConfig()->getType()->getInnerType());

        // Check that email field is disabled
        $this->assertTrue($form->get('email')->getConfig()->getOption('disabled'));

        // Check that roles field is configured correctly
        $rolesField = $form->get('roles');
        $this->assertTrue($rolesField->getConfig()->getOption('expanded'));
        $this->assertTrue($rolesField->getConfig()->getOption('multiple'));
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'firstName' => 'Petar',
            'lastName' => 'Petrovic',
            'email' => 'petar.petrovic@example.com',
            'roles' => ['ROLE_ADMIN'],
            'isActive' => true,
            'isVerified' => true,
        ];

        $user = new User();
        $user->setEmail('petar.petrovic@example.com'); // Set this because the field is disabled in the form
        $form = $this->factory->create(UserEditType::class, $user);

        // Submit the form with test data
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        // Check that the form data was mapped to the entity
        $this->assertEquals('Petar', $user->getFirstName());
        $this->assertEquals('Petrovic', $user->getLastName());
        $this->assertEquals('petar.petrovic@example.com', $user->getEmail());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertTrue($user->isActive());
        $this->assertTrue($user->isVerified());
    }

    public function testConfigureOptions(): void
    {
        $form = $this->factory->create(UserEditType::class);
        $options = $form->getConfig()->getOptions();

        // Test that data_class is set to User::class
        $this->assertEquals(User::class, $options['data_class']);
    }
}

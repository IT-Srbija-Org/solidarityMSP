<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\ProfileEditType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

class ProfileEditTypeTest extends TypeTestCase
{
    public function testProfileEditFormHasCorrectFields(): void
    {
        $form = $this->factory->create(ProfileEditType::class);

        // Check that form contains the expected fields
        $this->assertTrue($form->has('firstName'));
        $this->assertTrue($form->has('lastName'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('submit'));

        // Get the form field types
        $this->assertInstanceOf(TextType::class, $form->get('firstName')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextType::class, $form->get('lastName')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(SubmitType::class, $form->get('submit')->getConfig()->getType()->getInnerType());

        // Check that email field is disabled
        $this->assertTrue($form->get('email')->getConfig()->getOption('disabled'));
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'firstName' => 'Petar',
            'lastName' => 'Petrovic',
            'email' => 'petar.petrovic@example.com',
        ];

        $user = new User();
        $user->setEmail('petar.petrovic@example.com'); // Set this because the field is disabled in the form
        $form = $this->factory->create(ProfileEditType::class, $user);

        // Submit the form with test data
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        // Check that the form data was mapped to the entity
        $this->assertEquals('Petar', $user->getFirstName());
        $this->assertEquals('Petrovic', $user->getLastName());
        $this->assertEquals('petar.petrovic@example.com', $user->getEmail());
    }

    public function testConfigureOptions(): void
    {
        $form = $this->factory->create(ProfileEditType::class);
        $options = $form->getConfig()->getOptions();

        // Test that data_class is set to User::class
        $this->assertEquals(User::class, $options['data_class']);
    }
}

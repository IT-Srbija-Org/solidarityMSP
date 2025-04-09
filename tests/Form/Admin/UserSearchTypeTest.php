<?php

namespace App\Tests\Form\Admin;

use App\Form\Admin\UserSearchType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

class UserSearchTypeTest extends TypeTestCase
{
    public function testUserSearchFormHasCorrectFields(): void
    {
        $form = $this->factory->create(UserSearchType::class);

        // Check that form contains the expected fields
        $this->assertTrue($form->has('firstName'));
        $this->assertTrue($form->has('lastName'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('role'));
        $this->assertTrue($form->has('isActive'));
        $this->assertTrue($form->has('isVerified'));
        $this->assertTrue($form->has('submit'));

        // Get the form field types
        $this->assertInstanceOf(TextType::class, $form->get('firstName')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextType::class, $form->get('lastName')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextType::class, $form->get('email')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(ChoiceType::class, $form->get('role')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(ChoiceType::class, $form->get('isActive')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(ChoiceType::class, $form->get('isVerified')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(SubmitType::class, $form->get('submit')->getConfig()->getType()->getInnerType());

        // Check method is GET
        $this->assertEquals('GET', $form->getConfig()->getMethod());

        // Check that all fields are optional
        $this->assertFalse($form->get('firstName')->getConfig()->getOption('required'));
        $this->assertFalse($form->get('lastName')->getConfig()->getOption('required'));
        $this->assertFalse($form->get('email')->getConfig()->getOption('required'));
        $this->assertFalse($form->get('role')->getConfig()->getOption('required'));
        $this->assertFalse($form->get('isActive')->getConfig()->getOption('required'));
        $this->assertFalse($form->get('isVerified')->getConfig()->getOption('required'));
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'firstName' => 'Petar',
            'lastName' => 'Petrovic',
            'email' => 'petar.petrovic@example.com',
            'role' => 'ROLE_ADMIN',
            'isActive' => true,
            'isVerified' => true,
        ];

        $form = $this->factory->create(UserSearchType::class);

        // Submit the form with test data
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        // Get the form data
        $data = $form->getData();

        // Check the form data
        $this->assertEquals('Petar', $data['firstName']);
        $this->assertEquals('Petrovic', $data['lastName']);
        $this->assertEquals('petar.petrovic@example.com', $data['email']);
        $this->assertEquals('ROLE_ADMIN', $data['role']);
        $this->assertEquals(true, $data['isActive']);
        $this->assertEquals(true, $data['isVerified']);
    }

    public function testConfigureOptions(): void
    {
        $form = $this->factory->create(UserSearchType::class);
        $options = $form->getConfig()->getOptions();

        // Test CSRF protection is disabled
        $this->assertFalse($options['csrf_protection']);

        // Test validation is disabled
        $this->assertFalse($options['validation_groups']);
    }

    public function testGetBlockPrefix(): void
    {
        $formType = new UserSearchType();

        // Test that the block prefix is empty (for GET forms with query parameters)
        $this->assertEquals('', $formType->getBlockPrefix());
    }
}

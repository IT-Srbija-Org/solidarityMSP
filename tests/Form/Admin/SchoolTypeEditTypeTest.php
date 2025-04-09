<?php

namespace App\Tests\Form\Admin;

use App\Entity\SchoolType;
use App\Form\Admin\SchoolTypeEditType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

class SchoolTypeEditTypeTest extends TypeTestCase
{
    public function testSchoolTypeEditFormHasCorrectFields(): void
    {
        $form = $this->factory->create(SchoolTypeEditType::class);

        // Check that form contains the expected fields
        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('submit'));

        // Get the form field types
        $this->assertInstanceOf(TextType::class, $form->get('name')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(SubmitType::class, $form->get('submit')->getConfig()->getType()->getInnerType());
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'name' => 'Osnovna škola',
        ];

        $schoolType = new SchoolType();
        $form = $this->factory->create(SchoolTypeEditType::class, $schoolType);

        // Submit the form with test data
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        // Check that the form data was mapped to the entity
        $this->assertEquals('Osnovna škola', $schoolType->getName());
    }

    public function testConfigureOptions(): void
    {
        $form = $this->factory->create(SchoolTypeEditType::class);
        $options = $form->getConfig()->getOptions();

        // Test that data_class is set to SchoolType::class
        $this->assertEquals(SchoolType::class, $options['data_class']);
    }
}

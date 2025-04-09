<?php

namespace App\Tests\Form\Admin;

use App\Entity\City;
use App\Form\Admin\CityEditType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

class CityEditTypeTest extends TypeTestCase
{
    public function testCityEditFormHasCorrectFields(): void
    {
        $form = $this->factory->create(CityEditType::class);

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
            'name' => 'Novi Sad',
        ];

        $city = new City();
        $form = $this->factory->create(CityEditType::class, $city);

        // Submit the form with test data
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        // Check that the form data was mapped to the entity
        $this->assertEquals('Novi Sad', $city->getName());
    }

    public function testConfigureOptions(): void
    {
        $form = $this->factory->create(CityEditType::class);
        $options = $form->getConfig()->getOptions();

        // Test that data_class is set to City::class
        $this->assertEquals(City::class, $options['data_class']);
    }
}

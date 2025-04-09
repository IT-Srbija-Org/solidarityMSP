<?php

namespace App\Tests\Form\Admin;

use App\Form\Admin\CitySearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

class CitySearchTypeTest extends TypeTestCase
{
    public function testCitySearchFormHasCorrectFields(): void
    {
        $form = $this->factory->create(CitySearchType::class);

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
            'name' => 'Beograd',
        ];

        $form = $this->factory->create(CitySearchType::class);

        // Submit the form with test data
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('Beograd', $form->getData()['name']);
    }
}

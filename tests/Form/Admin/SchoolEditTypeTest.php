<?php

namespace App\Tests\Form\Admin;

use App\Entity\City;
use App\Entity\School;
use App\Entity\SchoolType as EntitySchoolType;
use App\Form\Admin\SchoolEditType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

class SchoolEditTypeTest extends TypeTestCase
{
    protected function getExtensions()
    {
        // Mock entity manager
        $entityManager = $this->createMock(EntityManagerInterface::class);

        // Set up city repository mock
        $cityRepository = $this->createMock(EntityRepository::class);

        // Set up school type repository mock
        $schoolTypeRepository = $this->createMock(EntityRepository::class);

        // Configure the entity manager to return our repository mocks
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [City::class, $cityRepository],
                [EntitySchoolType::class, $schoolTypeRepository],
            ]);

        // Set up the registry mock
        $mockRegistry = $this->createMock(ManagerRegistry::class);
        $mockRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        return [
            new DoctrineOrmExtension($mockRegistry),
        ];
    }

    public function testSchoolEditFormHasCorrectFields(): void
    {
        $form = $this->factory->create(SchoolEditType::class);

        // Check that form contains the expected fields
        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('city'));
        $this->assertTrue($form->has('type'));
        $this->assertTrue($form->has('submit'));

        // Get the form field types
        $this->assertInstanceOf(TextType::class, $form->get('name')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(EntityType::class, $form->get('city')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(EntityType::class, $form->get('type')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(SubmitType::class, $form->get('submit')->getConfig()->getType()->getInnerType());

        // Validate entity field configurations
        $cityField = $form->get('city');
        $this->assertEquals(City::class, $cityField->getConfig()->getOption('class'));
        $this->assertEquals('name', $cityField->getConfig()->getOption('choice_label'));

        $typeField = $form->get('type');
        $this->assertEquals(EntitySchoolType::class, $typeField->getConfig()->getOption('class'));
        $this->assertEquals('name', $typeField->getConfig()->getOption('choice_label'));
    }

    public function testConfigureOptions(): void
    {
        $form = $this->factory->create(SchoolEditType::class);
        $options = $form->getConfig()->getOptions();

        // Test that data_class is set to School::class
        $this->assertEquals(School::class, $options['data_class']);
    }
}

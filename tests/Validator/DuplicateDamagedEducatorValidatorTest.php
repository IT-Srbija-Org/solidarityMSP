<?php

namespace App\Tests\Validator;

use App\Entity\DamagedEducator;
use App\Entity\DamagedEducatorPeriod;
use App\Repository\DamagedEducatorRepository;
use App\Validator\DuplicateDamagedEducator;
use App\Validator\DuplicateDamagedEducatorValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class DuplicateDamagedEducatorValidatorTest extends TestCase
{
    private DuplicateDamagedEducatorValidator $validator;
    private MockObject&DamagedEducatorRepository $repository;
    private MockObject&ExecutionContextInterface $context;
    private MockObject&ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(DamagedEducatorRepository::class);
        $this->validator = new DuplicateDamagedEducatorValidator($this->repository);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->violationBuilder->method('addViolation')->willReturn(null);
        $this->context->method('buildViolation')->willReturn($this->violationBuilder);

        $this->validator->initialize($this->context);
    }

    public function testNoDuplicateFound(): void
    {
        $educator = $this->createDamagedEducator(1, '160258563225775692');
        $this->repository->method('findBy')->willReturn([]);

        $constraint = new DuplicateDamagedEducator();

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($educator, $constraint);
    }

    public function testDuplicateWithSameIdIgnored(): void
    {
        $educator = $this->createDamagedEducator(1, '160258563225775692');
        $duplicate = $this->createDamagedEducator(1, '160258563225775692');

        $this->repository->method('findBy')->willReturn([$duplicate]);

        $constraint = new DuplicateDamagedEducator();

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($educator, $constraint);
    }

    public function testDuplicateWithDifferentIdTriggersViolation(): void
    {
        $educator = $this->createDamagedEducator(1, '160258563225775692');
        $duplicate = $this->createDamagedEducator(2, '160258563225775692');

        $this->repository->method('findBy')->willReturn([$duplicate]);

        $constraint = new DuplicateDamagedEducator();

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($this->violationBuilder);

        $this->violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('accountNumber')
            ->willReturn($this->violationBuilder);

        $this->violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate($educator, $constraint);
    }

    private function createDamagedEducator(int $id, string $accountNumber): DamagedEducator
    {
        $educator = new DamagedEducator();
        $reflection = new \ReflectionClass(DamagedEducator::class);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($educator, $id);

        $educator->setAccountNumber($accountNumber);
        $educator->setPeriod(new DamagedEducatorPeriod());

        return $educator;
    }
}

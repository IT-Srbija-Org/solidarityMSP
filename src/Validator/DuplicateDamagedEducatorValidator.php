<?php

namespace App\Validator;

use App\Entity\DamagedEducator;
use App\Repository\DamagedEducatorRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DuplicateDamagedEducatorValidator extends ConstraintValidator
{
    public function __construct(private DamagedEducatorRepository $damagedEducatorRepository)
    {
    }

    public function validate($damagedEducator, Constraint $constraint): void
    {
        if (!$damagedEducator instanceof DamagedEducator) {
            throw new UnexpectedTypeException($damagedEducator, DamagedEducator::class);
        }

        if (!$constraint instanceof DuplicateDamagedEducator) {
            throw new UnexpectedTypeException($constraint, DuplicateDamagedEducator::class);
        }

        $items = $this->damagedEducatorRepository->findBy([
            'accountNumber' => $damagedEducator->getAccountNumber(),
        ]);

        if (0 == count($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item->getId() == $damagedEducator->getId()) {
                continue;
            }

            $this->context
                ->buildViolation($constraint->message)
                ->atPath('accountNumber')
                ->addViolation();

            break;
        }
    }
}

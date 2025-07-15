<?php

namespace App\Validator;

use App\Entity\DamagedEducator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class MonthlyLimitValidator extends ConstraintValidator
{
    public function validate($damagedEducator, Constraint $constraint): void
    {
        if (!$damagedEducator instanceof DamagedEducator) {
            throw new UnexpectedTypeException($damagedEducator, DamagedEducator::class);
        }

        if (!$constraint instanceof MonthlyLimit) {
            throw new UnexpectedTypeException($constraint, MonthlyLimit::class);
        }

        $monthlyLimit = DamagedEducator::MONTHLY_LIMIT;

        if ($damagedEducator->getAmount() <= $monthlyLimit) {
            return;
        }

        $this->context
            ->buildViolation('Cifra ne može da bude veća od '.number_format($monthlyLimit, 2, ',', '.'))
            ->atPath('amount')
            ->addViolation();
    }
}

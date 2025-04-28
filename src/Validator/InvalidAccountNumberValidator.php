<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class InvalidAccountNumberValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof InvalidAccountNumber) {
            throw new UnexpectedTypeException($constraint, InvalidAccountNumber::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_string($value) || '' === $value) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }

        if (!$this->isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    private function isValid(string $accountNumber): bool
    {
        if (str_starts_with($accountNumber, '840')) {
            return false;
        }

        if (str_starts_with($accountNumber, '150')) {
            return false;
        }

        return true;
    }
}

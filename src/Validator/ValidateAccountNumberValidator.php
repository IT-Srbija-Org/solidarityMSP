<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidateAccountNumberValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidateAccountNumber) {
            throw new UnexpectedTypeException($constraint, ValidateAccountNumber::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_string($value) || '' === $value) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }

        // Budget of the Republic of Serbia
        if (str_starts_with($value, '840')) {
            $this->context->buildViolation('Broj računa pripada budzetu Republike Srbije.')
                ->addViolation();
        }

        // Eurobank Direktna
        if (str_starts_with($value, '150')) {
            $this->context->buildViolation('Broj računa pripada banci "Eurobank Direktna" koja više ne postoji.')
                ->addViolation();
        }

        // MTS Bank
        if (str_starts_with($value, '360')) {
            $this->context->buildViolation('Broj računa pripada banci "MTS Bank" koja više ne postoji.')
                ->addViolation();
        }

        if (!$this->validateAccountNumber($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    private function validateAccountNumber(string $accountNumber): bool
    {
        $controlNumber = $this->mod97(substr($accountNumber, 0, -2));

        return str_pad($controlNumber, 2, '0', STR_PAD_LEFT) === substr($accountNumber, -2);
    }

    private function mod97(string $accountNumber, int $base = 100): int
    {
        $controlNumber = 0;

        for ($x = strlen($accountNumber) - 1; $x >= 0; --$x) {
            $num = (int) $accountNumber[$x];
            $controlNumber = ($controlNumber + ($base * $num)) % 97;
            $base = ($base * 10) % 97;
        }

        return 98 - $controlNumber;
    }
}

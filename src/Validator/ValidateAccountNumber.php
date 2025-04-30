<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidateAccountNumber extends Constraint
{
    public string $message = 'Broj računa nije ispravan.';
}

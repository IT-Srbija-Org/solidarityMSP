<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class InvalidAccountNumber extends Constraint
{
    public string $message = 'Broj računa nije ispravan.';
}

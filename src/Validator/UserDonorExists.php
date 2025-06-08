<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UserDonorExists extends Constraint
{
    public string $message = 'Donator sa ovom email adresom ne postoji.';
}

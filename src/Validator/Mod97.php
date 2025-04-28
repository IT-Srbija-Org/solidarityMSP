<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Mod97 extends Constraint
{
    public string $message = 'Broj računa nije validan. Primeri validnog brojeva računa je: 265104031000361092.';
}

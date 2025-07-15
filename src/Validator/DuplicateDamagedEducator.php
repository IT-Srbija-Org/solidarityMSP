<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class DuplicateDamagedEducator extends Constraint
{
    public string $message = 'Već postoji prijavljen ovaj broj računa.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

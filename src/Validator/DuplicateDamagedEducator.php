<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class DuplicateDamagedEducator extends Constraint
{
    public string $message = 'Već je unešen broj računa za drugog oštećenog za isti period.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

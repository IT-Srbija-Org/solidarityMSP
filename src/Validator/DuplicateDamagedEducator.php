<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class DuplicateDamagedEducator extends Constraint
{
    public string $message = 'Već postoji isti broj računa za drugog oštećenog za isti period i školu.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

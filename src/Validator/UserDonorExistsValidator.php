<?php

namespace App\Validator;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UserDonorExistsValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UserDonorExists) {
            throw new UnexpectedTypeException($constraint, UserDonorExists::class);
        }

        if (null === $value) {
            return;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $value]);
        if (empty($user)) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('userDonorEmail')
                ->addViolation();

            return;
        }

        if (empty($user->getUserDonor())) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('userDonorEmail')
                ->addViolation();
        }
    }
}

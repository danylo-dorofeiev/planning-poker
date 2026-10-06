<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CardValueValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $length = grapheme_strlen($value);

        // 4 symbols
        if (
            $length <= 4 &&
            !preg_match('/\p{Extended_Pictographic}/u', $value)
        ) {
            return;
        }

        // 1 emoji
        if (
            $length === 1 &&
            preg_match('/\p{Extended_Pictographic}/u', $value)
        ) {
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->addViolation();
    }
}
